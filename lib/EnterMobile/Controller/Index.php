<?php

namespace EnterMobile\Controller;

use Enter\Http;
use EnterAggregator\AbTestTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Index as Page;

class Index {
    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait,
        AbTestTrait,
        RouterTrait
    ;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $router = $this->getRouter();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\Index();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->httpRequest = $request;
        // ответ
        $controllerResponse = $controller->execute($controllerRequest);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Index\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->promos = $controllerResponse->promos;
        $pageRequest->popularBrands = call_user_func(function() use($router) {
            $brands = (new \EnterRepository\Brand())->getPopularObjects();
            foreach ($brands as $brand) {
                $brand->url = $router->getUrlByRoute(new \EnterMobile\Routing\ProductSlice\Get($brand->sliceId));
            }

            return $brands;
        });

        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\Index())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();

        if ('disabled' === $this->getAbTest()->getObjectByToken('main')->chosenItem->token) {
            $renderer->setPartials([
                'content' => 'page/main/content',
            ]);
            $content = $renderer->render('layout/default', $page);
        } else {
            $renderer->setPartials([
                'content' => 'page/main/content_updated',
            ]);
            $content = $renderer->render('layout/default-1511', $page);
        }

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}