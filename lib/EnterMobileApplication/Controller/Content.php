<?php

namespace EnterMobileApplication\Controller {

    use Enter\Curl\Client;
    use Enter\Http;
    use EnterAggregator\LoggerTrait;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\TemplateHelperTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller\Content\Response;

    class Content {
        use ConfigTrait, CurlTrait, TemplateHelperTrait, LoggerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionQuery);

            $contentToken = $request->query['contentId'];
            if (!$contentToken) {
                throw new \Exception('Не передан contentId', Http\Response::STATUS_BAD_REQUEST);
            }

            $contentItemQuery = new Query\Content\GetItemByToken($contentToken, ['app-mobile']);
            $curl->prepare($contentItemQuery);

            $curl->execute();

            $contentPage = new \EnterModel\Content\Page($contentItemQuery->getResult());

            if (!$contentPage->contentHtml || !$contentPage->isAvailableByDirectLink)
                return (new \EnterMobileApplication\Controller\Error\NotFound())->execute($request, sprintf('Контент @%s не найден', $contentToken));

            $contentPage->contentHtml = '<script src="http://yandex.st/jquery/1.8.3/jquery.js" type="text/javascript"></script>' . "\n" . $contentPage->contentHtml;

            // ответ
            $response = new Response();
            $response->content = $contentPage->contentHtml;
            // TODO: вынести в EnterRepository\Content
            $response->content = $this->processContentLinks($response->content, $curl, $region->id);
            $response->content = $this->removeExternalScripts($response->content);
            $response->content = preg_replace('/<iframe(?:\s[^>]*)?>.*?<\/iframe>/is', '', $response->content); // https://jira.enter.ru/browse/TERMINALS-862
            $response->title = $contentPage->title ? $contentPage->title : null;

            return new Http\JsonResponse($response);
        }

        private function processContentLinks($content, Client $curl, $regionId) {
            if (preg_match_all('/<a\s+[^>]*href="(?:http:\/\/(?:www\.)?enter\.ru)?(\/[^"]*)"/i', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                $contentRepository = new \EnterRepository\Content();

                foreach ($matches as $key => $match) {
                    $path = $this->getPathFromHrefAttributeValue($match[1][0]);

                    if (0 === strpos($path, '/catalog/')) {
                        $matches[$key]['query'] = new Query\Product\Category\GetItemByToken($contentRepository->getTokenByPath($path), $regionId);
                        $curl->prepare($matches[$key]['query']);
                    }
                    else if (0 === strpos($path, '/product/')) {
                        $matches[$key]['query'] = new Query\Product\GetItemByToken($contentRepository->getTokenByPath($path), $regionId, ['model' => false, 'related' => false]);
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
                    $newAttributes = null;

                    try {
                        if (0 === strpos($path, '/catalog/')) {
                            $category = $categoryRepository->getObjectByQuery($match['query']);
                            if (null !== $category)
                                $newAttributes = ' data-type="ProductCatalog/Category" data-category-id="' . $this->getTemplateHelper()->escape($category->id) . '"';
                        }
                        else if (0 === strpos($path, '/product/')) {
                            $product = $productRepository->getObjectByQuery($match['query']);
                            if (null !== $product)
                                $newAttributes = ' data-type="ProductCard" data-product-id="' . $this->getTemplateHelper()->escape($product->id) . '"';
                        }
                        else if (0 === strpos($path, '/')) {
                            $newAttributes = ' data-type="Content" data-content-token="' . $contentRepository->getTokenByPath($path) . '"';
                        }
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'content']]);
                    }

                    if ($newAttributes !== null) {
                        $content = substr($content, 0, $linkTagEndPos) . $newAttributes . substr($content, $linkTagEndPos);
                        $shift += strlen($newAttributes);
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

namespace EnterMobileApplication\Controller\Content {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $content;
        /** @var string */
        public $title;
    }
}