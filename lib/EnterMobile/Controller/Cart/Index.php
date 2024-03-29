<?php

namespace EnterMobile\Controller\Cart;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Cart\Index as Page;
use EnterMobile\Repository;

class Index {
    use ConfigTrait, LoggerTrait, CurlTrait, RouterTrait, SessionTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // пользователь
        $user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        if ($user) {
            $controller = new \EnterAggregator\Controller\Cart\Update();
            $controllerRequest = $controller->createRequest();
            $controllerRequest->regionId = $region->id;
            $controllerRequest->userUi = $user->ui;
            $controller->execute($controllerRequest);
        }

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        $cartProductIds = array_values(array_map(function(\EnterModel\Cart\Product $cartProduct) { return $cartProduct->id; }, $cart->product));

        $descriptionListQuery = null;
        if ($cartProductIds) {
            $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                $cartProductIds,
                [
                    'media'    => true,
                    'category' => true,
                    'label'    => true,
                    'brand'    => true,
                ]
            );
            $curl->prepare($descriptionListQuery);
        }

        // запрос дерева категорий для меню
        $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
        $curl->prepare($categoryTreeQuery);

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        $curl->execute();
        
        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery, $descriptionListQuery);

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Cart\Index\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->user = $user;
        $pageRequest->cart = $cart;

        // страница
        $page = new Page();
        (new Repository\Page\Cart\Index())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/cart/content',
        ]);
        $content = $renderer->render('layout/without-footer', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}