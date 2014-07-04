<?php

namespace EnterTerminal\Controller;

use Enter\Curl\Client;
use Enter\Http;
use EnterSite\ViewHelperTrait;
use EnterTerminal\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\Controller;
use EnterTerminal\Repository;
use EnterCurlQuery as Query;
use EnterTerminal\Model\Page\Content as Page;

class Content {
    use ConfigTrait, CurlClientTrait, ViewHelperTrait {
        ConfigTrait::getConfig insteadof CurlClientTrait;
    }

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurlClient();

        // ид магазина
        $shopId = (new Repository\Shop())->getIdByHttpRequest($request);

        // запрос магазина
        $shopItemQuery = new Query\Shop\GetItemById($shopId);
        $curl->prepare($shopItemQuery);

        $curl->execute();

        // магазин
        $shop = (new Repository\Shop())->getObjectByQuery($shopItemQuery);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
        }

        $contentToken = $request->query['contentToken'];
        if (!$contentToken) {
            throw new \Exception('Не передан contentToken');
        }

        $contentItemQuery = new Query\Content\GetItemByToken($contentToken);
        $curl->prepare($contentItemQuery);

        $curl->execute();

        // страница
        $page = new Page();

        $item = $contentItemQuery->getResult();
        $page->content = $item['content'];
        $page->content = $this->processContentLinks($page->content, $curl, $shop->regionId);
        $page->content = $this->removeExternalScripts($page->content);
        $page->title = isset($item['title']) ? $item['title'] : null;

        return new Http\JsonResponse($page);
    }

    private function processContentLinks($content, Client $curl, $regionId) {
        if (preg_match_all('/<a\s+[^>]*href="(?:http:\/\/(?:www\.)?enter\.ru)?(\/[^"]*)"/i', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $key => $match) {
                $path = $this->getPathFromHrefAttributeValue($match[1][0]);

                if (0 === strpos($path, '/catalog/')) {
                    $matches[$key]['query'] = new Query\Product\Category\GetItemByToken($this->getTokenByUrl($path), $regionId);
                    $curl->prepare($matches[$key]['query']);
                }
                else if (0 === strpos($path, '/product/')) {
                    $matches[$key]['query'] = new Query\Product\GetItemByToken($this->getTokenByUrl($path), $regionId);
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

                if (0 === strpos($path, '/catalog/')) {
                    $category = $categoryRepository->getObjectByQuery($match['query']);
                    if (null !== $category)
                        $newAttributes = ' data-type="ProductCatalog/Category" data-category-id="' . $this->getViewHelper()->escape($category->id) . '"';
                }
                else if (0 === strpos($path, '/product/')) {
                    $product = $productRepository->getObjectByQuery($match['query']);
                    if (null !== $product)
                        $newAttributes = ' data-type="ProductCard" data-product-id="' . $this->getViewHelper()->escape($product->id) . '"';
                }
                else if (0 === strpos($path, '/')) {
                    $newAttributes = ' data-type="Content" data-content-token="' . $this->getTokenByUrl($path) . '"';
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

    private function getTokenByUrl($url) {
        if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        }

        $segments = explode('/', $url);
        // TODO: добавить поддержку для путей из 4х сегментов
        if (count($segments) <= 3)
            return end($segments);

        return null;
    }

    private function removeExternalScripts($content) {
        $content = preg_replace('/<script(?:\s+[^>]*)?>\s*\/\*\s*build:::7\s*\*\/\s*var\s+liveTex\s.*?<\/script>/is', '', $content);
        $content = preg_replace('/<!-- AddThis Button BEGIN -->.*?<!-- AddThis Button END -->/is', '', $content);
        return $content;
    }
}