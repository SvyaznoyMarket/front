<?php

namespace EnterMobile\Controller\User\Address;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\User\Address\Index as Page;

class Index {
    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        $curl->execute();

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        $userMenu = (new \EnterRepository\UserMenu())->getMenuItems();

        //запрос для получения страницы
        $pageRequest = new Repository\Page\User\Address\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $region;
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->cart = $cart;
        $pageRequest->userMenu = $userMenu;

        $page = new Page();
        (new Repository\Page\User\Address())->buildObjectByRequest($page, $pageRequest);

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/address'
        ]);

        $content = $renderer->render('layout/footerless', $page);

        return new Http\Response($content);
    }
}