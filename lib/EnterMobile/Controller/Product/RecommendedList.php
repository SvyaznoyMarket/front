<?php

namespace EnterMobile\Controller\Product;

use Enter\Http;
use EnterAggregator\MustacheRendererTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Product\RecommendedList as Page;

class RecommendedList {
    use MustacheRendererTrait;

    public function execute(Http\Request $request) {
        $productRepository = new \EnterRepository\Product();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // ид товара
        $productId = $productRepository->getIdByHttpRequest($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\Product\RecommendedListByProduct();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->config->alsoBought = true;
        $controllerRequest->config->similar = true;
        $controllerRequest->config->removeUnavailable = true;
        $controllerRequest->regionId = $regionId;
        $controllerRequest->productIds = [$productId];
        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Product\RecommendedList\Request();
        $pageRequest->product = reset($controllerResponse->productsById);
        $pageRequest->recommendedProductsById = $controllerResponse->recommendedProductsById;
        $pageRequest->alsoBoughtIdList = $controllerResponse->alsoBoughtIdList;
        $pageRequest->alsoViewedIdList = $controllerResponse->alsoViewedIdList;
        $pageRequest->similarIdList = $controllerResponse->similarIdList;
        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\Product\RecommendedList())->buildObjectByRequest($page, $pageRequest);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return new Http\JsonResponse([
            'result' => $page,
        ]);
    }
}