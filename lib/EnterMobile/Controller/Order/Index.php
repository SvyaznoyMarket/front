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
use EnterMobile\Model\Page\Order\Index as Page;

class Index {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();

        // запрос пользователя
        $userItemQuery = null;
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Order\Index\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->formErrors = (array)$session->flashBag->get('orderForm.error');
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->userData = $session->get($config->order->userSessionKey);

        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\Order\Index())->buildObjectByRequest($page, $pageRequest, $session->get($config->order->userSessionKey));

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/order/index/content',
        ]);
        $content = $renderer->render('layout/simple', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}