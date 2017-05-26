<?php

namespace EnterMobile\Controller\Order\Payment;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Controller;
use EnterMobile\Controller\Order\ControllerTrait;

class Fail {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, RouterTrait, MustacheRendererTrait, DebugContainerTrait;
    use ControllerTrait {
        ConfigTrait::getConfig insteadof ControllerTrait;
    }

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $session = $this->getSession();
        $cartSessionKey = $this->getCartSessionKeyByHttpRequest($request);

        $regionRepository = new \EnterRepository\Region();

        // ид региона
        $regionId = $regionRepository->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        $region = $regionRepository->getObjectByQuery($regionQuery);

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $cartSessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        $curl->execute();

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        $pageRequest = new \EnterMobile\Repository\Page\Order\Payment\Fail\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->cart = $cart;

        // страница
        $page = new \EnterMobile\Model\Page\Order\Payment\Fail();
        (new \EnterMobile\Repository\Page\Order\Payment\Fail())->buildObjectByRequest($page, $pageRequest);

        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/order/payment/fail/content',
        ]);
        $content = $renderer->render('layout/simple', $page);
        return new Http\Response($content);
    }
}