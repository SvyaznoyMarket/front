<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterMobile\Model\Page\Order\Delivery as Page;

class Delivery {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // изменения
        $changeData = $request->data['change'] ?: null;

        // предыдущее разбиение
        $previousSplitData = null;
        if ($changeData) {
            $previousSplitData = $session->get($config->order->splitSessionKey);
        }

        // корзина
        $cart = $cartRepository->getObjectByHttpSession($session);

        // контроллер
        $controller = new \EnterAggregator\Controller\Cart\Split();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->shopId = null;
        $controllerRequest->changeData = $changeData;
        $controllerRequest->previousSplitData = $previousSplitData;
        $controllerRequest->cart = $cart;
        // при получении данных о разбиении корзины - записать их в сессию немедленно
        $controllerRequest->splitReceivedSuccessfullyCallback->handler = function() use (&$controllerRequest, &$config, &$session) {
            $session->set($config->order->splitSessionKey, $controllerRequest->splitReceivedSuccessfullyCallback->splitData);
        };
        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        $region = $controllerResponse->region;
        $errors = $controllerResponse->errors;
        $split = $controllerResponse->split;

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Order\Delivery\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $region;
        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\Order\Delivery())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/order/delivery/content',
        ]);
        $content = $renderer->render('layout/simple', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}