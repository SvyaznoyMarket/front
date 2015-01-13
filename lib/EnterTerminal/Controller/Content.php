<?php

namespace EnterTerminal\Controller {

    use Enter\Curl\Client;
    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\TemplateHelperTrait;
    use EnterTerminal\Controller;
    use EnterTerminal\Repository;
    use EnterQuery as Query;
    use EnterTerminal\Controller\Content\Response;

    class Content {
        use ConfigTrait, CurlTrait, TemplateHelperTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $contentToken = $request->query['contentToken'];
            if (!$contentToken) {
                throw new \Exception('Не передан contentToken', Http\Response::STATUS_BAD_REQUEST);
            }

            $contentItemQuery = new Query\Content\GetItemByToken($contentToken);
            $curl->prepare($contentItemQuery);

            $curl->execute();

            if ($contentItemQuery->getError() && $contentItemQuery->getError()->getCode() === 404)
                return (new \EnterTerminal\Controller\Error\NotFound())->execute($request, sprintf('Контент @%s не найден', $contentToken));

            $item = $contentItemQuery->getResult();

            // ответ
            $response = new Response();
            $response->content = $item['content'];
            $response->content = $this->processContentLinks($response->content, $curl, $regionId);
            $response->content = $this->removeExternalScripts($response->content);
            $response->content = preg_replace('/<iframe(?:\s[^>]*)?>.*?<\/iframe>/is', '', $response->content); // https://jira.enter.ru/browse/TERMINALS-862
            $response->title = isset($item['title']) ? $item['title'] : null;

            return new Http\JsonResponse($response);
        }

        private function processContentLinks($content, Client $curl, $regionId) {
            $templateHelper = $this->getTemplateHelper();

            if (preg_match_all('/<a\s+[^>]*href="(?:http:\/\/(?:www\.)?enter\.ru)?(\/[^"]*)"/i', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                $contentRepository = new \EnterRepository\Content();

                foreach ($matches as $key => $match) {
                    $path = $this->getPathFromHrefAttributeValue($match[1][0]);

                    if (preg_match('/\/catalog\/slice\/([\w\d-_]+)/', $path)) { // /catalog/slice/{sliceToken}
                        // Для данного вида слайсов запросов к ядру не требуется
                    } else if (preg_match('/\/slices\/([\w\d-_]+)\/([\w\d-_]+)/', $path, $sliceMatches)) { //  /slices/{sliceToken}/{categoryToken}
                        $matches[$key]['query'] = new Query\Product\Category\GetItemByToken($sliceMatches[2], $regionId);
                        $curl->prepare($matches[$key]['query']);
                    } else if (preg_match('/\/slices\/([\w\d-_]+)/', $path)) { //   /slices/{sliceToken}
                        // Для данного вида слайсов запросов к ядру не требуется
                    } else if (0 === strpos($path, '/catalog/')) {
                        $matches[$key]['query'] = new Query\Product\Category\GetItemByToken($contentRepository->getTokenByPath($path), $regionId);
                        $curl->prepare($matches[$key]['query']);
                    } else if (0 === strpos($path, '/product/')) {
                        $matches[$key]['query'] = new Query\Product\GetItemByToken($contentRepository->getTokenByPath($path), $regionId);
                        $curl->prepare($matches[$key]['query']);
                    } else if (0 === strpos($path, '/products/set/')) {
                        $matches[$key]['query'] = new Query\Product\GetListByBarcodeList($contentRepository->getProductBarcodesByPath($path), $regionId);
                        $curl->prepare($matches[$key]['query']);
                    }
                }

                $curl->execute();
                $categoryRepository = new \EnterRepository\Product\Category();
                $productRepository = new \EnterRepository\Product();

                $shift = 0;
                foreach ($matches as $match) {
                    $linkTagEndPos = $match[0][1] + strlen($match[0][0]) + $shift;
                    $path = $this->getPathFromHrefAttributeValue($match[1][0]);
                    $attributes = null;

                    if (preg_match('/\/catalog\/slice\/([\w\d-_]+)/', $path, $sliceMatches)) { // /catalog/slice/{sliceToken}
                        $attributes = ' data-type="ProductCatalog/Slice" data-slice-token="' . $templateHelper->escape($sliceMatches[1]) . '"';
                    } else if (preg_match('/\/slices\/([\w\d-_]+)\/([\w\d-_]+)/', $path, $sliceMatches)) { //  /slices/{sliceToken}/{categoryToken}
                        $attributes = ' data-type="ProductCatalog/Slice" data-slice-token="' . $templateHelper->escape($sliceMatches[1]) . '"';
                        $category = $categoryRepository->getObjectByQuery($match['query']);
                        if ($category) {
                            $attributes .= ' data-category-id="' . $templateHelper->escape($category->id) . '"';
                        }
                    } else if (preg_match('/\/slices\/([\w\d-_]+)/', $path, $sliceMatches)) { //   /slices/{sliceToken}
                        $attributes = ' data-type="ProductCatalog/Slice" data-slice-token="' . $templateHelper->escape($sliceMatches[1]) . '"';
                    } else if (0 === strpos($path, '/catalog/')) {
                        $category = $categoryRepository->getObjectByQuery($match['query']);
                        if ($category) {
                            $attributes = ' data-type="ProductCatalog/Category" data-category-id="' . $templateHelper->escape($category->id) . '"';
                        }
                    } else if (0 === strpos($path, '/product/')) {
                        $product = $productRepository->getObjectByQuery($match['query']);
                        if ($product) {
                            $attributes = ' data-type="ProductCard" data-product-id="' . $templateHelper->escape($product->id) . '"';
                        }
                    } else if (0 === strpos($path, '/products/set/')) {
                        $productIds = array_map(
                            function(\EnterModel\Product $product) { return $product->id; },
                            $productRepository->getIndexedObjectListByQueryList([$match['query']])
                        );

                        if ((bool)$productIds) {
                            $attributes = ' data-type="ProductList" data-product-id="' . implode(',', $productIds) . '"';
                        }
                    } else if (0 === strpos($path, '/')) {
                        $attributes = ' data-type="Content" data-content-token="' . $contentRepository->getTokenByPath($path) . '"';
                    }

                    if ($attributes !== null) {
                        $content = substr($content, 0, $linkTagEndPos) . $attributes . substr($content, $linkTagEndPos);
                        $shift += strlen($attributes);
                    }
                }
            }

            return $content;
        }

        private function getPathFromHrefAttributeValue($href) {
            $url = html_entity_decode($href);
            $url = preg_replace('/\?.*$|\#.*$/s', '', $url);

            return $url;
        }

        private function removeExternalScripts($content) {
            $content = preg_replace('/<script(?:\s+[^>]*)?>\s*\/\*\s*build:::7\s*\*\/\s*var\s+liveTex\s.*?<\/script>/is', '', $content);
            $content = preg_replace('/<!-- AddThis Button BEGIN -->.*?<!-- AddThis Button END -->/is', '', $content);

            return $content;
        }
    }
}

namespace EnterTerminal\Controller\Content {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $content;
        /** @var string */
        public $title;
    }
}