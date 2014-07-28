<?php

namespace EnterTerminal\Controller;

use Enter\Http;
use EnterAggregator\Model\Context;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterTerminal\Controller;
use EnterTerminal\Model\Page\ProductCard as Page;

class ProductCard {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        // ид магазина
        $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request); // FIXME

        // ид товара
        $productId = trim((string)$request->query['productId']);
        if (!$productId) {
            throw new \Exception('Не указан параметр productId');
        }

        // запрос магазина
        $shopItemQuery = new Query\Shop\GetItemById($shopId);
        $curl->prepare($shopItemQuery);

        $curl->execute();

        // магазин
        $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
        }

        // запрос товара
        $productItemQuery = new Query\Product\GetItemById($productId, $shop->regionId);
        $curl->prepare($productItemQuery);

        $curl->execute();

        $context = new Context();
        $context->mainMenu = false;
        $controllerResponse = (new \EnterAggregator\Controller\ProductCard())->execute($shop->regionId, ['id' => $productId], $context);
        // товар
        if (!$controllerResponse->product) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Товар #%s не найден', $productId));
        }

        // страница
        $page = new Page();
        $page->catalogConfig = $controllerResponse->catalogConfig;
        $page->product = $controllerResponse->product;
        $page->reviews = $controllerResponse->product ? $controllerResponse->product->reviews : []; // FIXME: удалить
        $page->kitProducts = $controllerResponse->product ? $controllerResponse->product->relation->kits : []; // FIXME: удалить

        return new Http\JsonResponse($page);
    }
}