<?php

namespace EnterMobile\Controller;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\ShopCard as Page;

class ShopCard {

    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $renderer = $this->getRenderer();
        $curl = $this->getCurl();

        $requestQuery = $request->query->all();
        $shopToken = $requestQuery['shopToken'];

        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\ShopCard();

        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->httpRequest = $request;
        $controllerRequest->shopToken = $shopToken;
        // ответ
        $controllerResponse = $controller->execute($controllerRequest);

        $pageRequest = new Repository\Page\ShopCard\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->pointDescription = $controllerResponse->pointDescription;

        $page = new Page();
        (new Repository\Page\ShopCard())->buildObjectByRequest($page, $pageRequest);

        $renderer->setPartials([
            'content' => 'page/shops/card',
        ]);
        $content = $renderer->render('layout/shops', $page);


        return new Http\Response($content);
    }
}