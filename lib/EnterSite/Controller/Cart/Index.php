<?php

namespace EnterSite\Controller\Cart;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterSite\Routing;
use EnterSite\Controller;
use EnterQuery as Query;
use EnterSite\Model;
use EnterSite\Model\Page\Cart\Index as Page;
use EnterSite\Repository;

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
        $cartRepository = new \EnterRepository\Cart();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $cartItemQuery = new Query\Cart\GetItem($cart, $regionId);
        $curl->prepare($cartItemQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        $productListQuery = (bool)$cart->product ? new Query\Product\GetListByIdList(array_values(array_map(function(\EnterModel\Cart\Product $cartProduct) { return $cartProduct->id; }, $cart->product)), $region->id) : null;
        if ($productListQuery) {
            $curl->prepare($productListQuery);
        }

        // корзина из ядра
        $cart = $cartRepository->getObjectByQuery($cartItemQuery);

        // запрос дерева категорий для меню
        $categoryListQuery = new Query\Product\Category\GetTreeList($region->id, 3);
        $curl->prepare($categoryListQuery);

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        $curl->execute();

        $cartProducts = $cart->product;
        $productsById = $productListQuery ? (new \EnterRepository\Product)->getIndexedObjectListByQueryList([$productListQuery]) : [];

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Cart\Index\Request();
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->cart = $cart;
        $pageRequest->productsById = $productsById;
        $pageRequest->cartProducts = $cartProducts;

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
        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}