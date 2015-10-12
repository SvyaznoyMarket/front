<?php

namespace EnterMobile\Controller\Shop;

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
use EnterMobile\Model\Page\Shops\Index as Page;

class Index {

    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $renderer = $this->getRenderer();
        $config = $this->getConfig();

        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\Shop\Index();

        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->httpRequest = $request;
        // ответ
        $controllerResponse = $controller->execute($controllerRequest);


        $pageRequest = new Repository\Page\Shops\Index\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->points = $controllerResponse->points;


        $page = new Page();
        (new Repository\Page\Shops\Index())->buildObjectByRequest($page, $pageRequest);

        $renderer->setPartials([
            'content' => 'page/shops/index',
        ]);
        $content = $renderer->render('layout/shops', $page);


        return new Http\Response($content);
    }
}