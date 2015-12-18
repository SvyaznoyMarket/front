<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\User\Orders as Page;

class Orders {
    use ConfigTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\User\Orders();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->httpRequest = $request;
        // ответ
        $controllerResponse = $controller->execute($controllerRequest);

        if ($controllerResponse->redirect) {
            return $controllerResponse->redirect;
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\User\Orders\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->orders = $controllerResponse->orders;
        $pageRequest->userMenu = $controllerResponse->userMenu;

        $page = new Page();
        (new Repository\Page\User\Orders())->buildObjectByRequest($page, $pageRequest);
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;

        // рендер
        $renderer = $this->getRenderer();

        if ($request->isXmlHttpRequest()) {
            $response = new Http\JsonResponse([
                'content' => $renderer->render('page/private/order/content', $page->content),
            ]);
        } else {
            $renderer->setPartials([
                'content' => 'page/private/orders'
            ]);
            $content = $renderer->render('layout/footerless', $page);

            $response = new Http\Response($content);
        }

        return $response;
    }
}