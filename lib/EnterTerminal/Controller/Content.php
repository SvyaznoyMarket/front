<?php

namespace EnterTerminal\Controller;

use Enter\Curl\Client;
use Enter\Http;
use EnterSite\ViewHelperTrait;
use EnterTerminal\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\Controller;
use EnterTerminal\Repository;
use EnterSite\Curl\Query;
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
        $page->content = $this->processContentLinks($item['content'], $curl, $shop->regionId);
        $page->title = isset($item['title']) ? $item['title'] : null;

        return new Http\JsonResponse($page);
    }

    private function processContentLinks($content, Client $curl, $regionId) {
        if (preg_match_all('/<a\s+[^>]*href="((?:http:\/\/www\.enter\.ru)?\/[^"]*)"/i', $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $key => $match) {
                $url = $this->getUrlFromHrefAttributeValue($match[1][0]);

                if (strpos($url, '/catalog/') === 0) {
                    $matches[$key]['query'] = new Query\Product\Category\GetItemByToken($this->getTokenByUrl($url), $regionId);
                    $curl->prepare($matches[$key]['query']);
                }
                else if (strpos($url, '/product/') === 0) {
                    $matches[$key]['query'] = new Query\Product\GetItemByToken($this->getTokenByUrl($url), $regionId);
                    $curl->prepare($matches[$key]['query']);
                }
            }

            $curl->execute();
            $categoryRepository = new \EnterRepository\Product\Category();
            $productRepository = new \EnterRepository\Product();

            $shift = 0;
            foreach ($matches as $match) {
                $linkTagEndPos = $match[0][1] + strlen($match[0][0]) + $shift;
                $url = $this->getUrlFromHrefAttributeValue($match[1][0]);

                if (strpos($url, '/catalog/') === 0) {
                    $category = $categoryRepository->getObjectByQuery($match['query']);
                    $newAttributes = ' data-type="ProductCatalog/Category" data-category-id="' . $this->getViewHelper()->escape($category->id) . '"';
                }
                else if (strpos($url, '/product/') === 0) {
                    $product = $productRepository->getObjectByQuery($match['query']);
                    $newAttributes = ' data-type="ProductCard" data-product-id="' . $this->getViewHelper()->escape($product->id) . '"';
                }
                else if (strpos($url, 'http://www.enter.ru/') === 0) {
                    $newAttributes = ' data-type="Content" data-content-token="' . $this->getTokenByUrl($url) . '"';
                }
                else {
                    $newAttributes = null;
                }

                if ($newAttributes !== null) {
                    $content = substr($content, 0, $linkTagEndPos) . $newAttributes . substr($content, $linkTagEndPos);
                    $shift += strlen($newAttributes);
                }
            }
        }

        return $content;
    }

    private function getUrlFromHrefAttributeValue($href) {
        $url = html_entity_decode($href);
        $url = preg_replace('/\?.*$|\#.*$/s', '', $url);
        return $url;
    }

    private function getTokenByUrl($url) {
        if (strpos($url, 'http://www.enter.ru/') === 0) {
            $url = substr($url, strlen('http://www.enter.ru/'));
        }
        else if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        }

        $segments = explode('/', $url);
        // TODO: добавить поддержку для путей из 4х сегментов
        if (count($segments) <= 3)
            return end($segments);

        return null;
    }
}