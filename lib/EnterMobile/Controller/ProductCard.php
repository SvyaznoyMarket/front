<?php

namespace EnterMobile\Controller;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\Model\Context;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\ProductCard as Page;

class ProductCard {
    use ConfigTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $productRepository = new \EnterRepository\Product();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен товара
        $productToken = $productRepository->getTokenByHttpRequest($request);

        $context = new Context();
        $context->mainMenu = true;
        $controllerResponse = (new \EnterAggregator\Controller\ProductCard())->execute(
            $regionId,
            ['token' => $productToken],
            $context
        );
        if (!$controllerResponse->product) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Товар @%s не найден', $productToken));
        }
        if ($controllerResponse->product->link !== $request->getPathInfo()) {
            return (new Controller\Redirect())->execute($controllerResponse->product->link . ((bool)$request->getQueryString() ? ('?' . $request->getQueryString()) : ''), Http\Response::STATUS_MOVED_PERMANENTLY);
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCard\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->product = $controllerResponse->product;
        $pageRequest->accessoryCategories = $controllerResponse->accessoryCategories;
        $pageRequest->hasCredit = $controllerResponse->hasCredit;
        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\ProductCard())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/product-card/content',
        ]);
        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}