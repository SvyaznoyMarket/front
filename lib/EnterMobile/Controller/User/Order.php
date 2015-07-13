<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\User\Order as Page;

class Order {

    use ConfigTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
        // контроллер
        $controller = new \EnterAggregator\Controller\User\Order();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->httpRequest = $request;
        // ответ
        $controllerResponse = $controller->execute($controllerRequest);

        if ($controllerResponse->redirect) {
            return $controllerResponse->redirect;
        }

        //запрос для получения страницы
        $pageRequest = new Repository\Page\User\Order\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->order = $controllerResponse->order;

        $page = new Page();
        (new Repository\Page\User\Order())->buildObjectByRequest($page, $pageRequest);

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/order'
        ]);

        $content = $renderer->render('layout/default', $page);

        return new Http\Response($content);
    }
}