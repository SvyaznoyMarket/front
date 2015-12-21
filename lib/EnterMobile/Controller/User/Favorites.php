<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\User\Favorites as Page;

class Favorites {

    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\User\Favorites();
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
        $pageRequest = new Repository\Page\User\Favorites\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->favoriteProducts = $controllerResponse->favoriteProducts;
        $pageRequest->userMenu = $controllerResponse->userMenu;

        $page = new Page();
        (new Repository\Page\User\Favorites())->buildObjectByRequest($page, $pageRequest);
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;

        // рендер
        $renderer = $this->getRenderer();

        if ($request->isXmlHttpRequest()) {
            $response = new Http\JsonResponse([
                'content' => $renderer->render('page/private/favorite/content', $page->content),
            ]);
        } else {
            $renderer->setPartials([
                'content' => 'page/private/favorites'
            ]);
            $content = $renderer->render('layout/footerless', $page);

            $response = new Http\Response($content);
        }

        return $response;
    }
}