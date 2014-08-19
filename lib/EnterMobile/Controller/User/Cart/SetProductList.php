<?php

namespace EnterMobile\Controller\User\Cart;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Repository;
use EnterMobile\Model\Page\User\Cart\SetProduct as Page;

class SetProductList {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session);

        // товара для корзины
        $cartProducts = $cartRepository->getProductListByHttpRequest($request);
        if (!(bool)$cartProducts) {
            throw new \Exception('Товары не получены');
        }

        // добавление товаров в корзину
        foreach ($cartProducts as $cartProduct) {
            $cartRepository->setProductForObject($cart, $cartProduct);
        }

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен пользователя
        $userToken = (new \EnterRepository\User)->getTokenByHttpRequest($request);

        // запрос пользователя
        $userItemQuery = $userToken ? new Query\User\GetItemByToken($userToken) : null;
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        // запрос корзины
        $cartItemQuery = new Query\Cart\GetItem($cart, $regionId);
        $curl->prepare($cartItemQuery);

        // запрос товаров
        $productsById = [];
        foreach ($cart->product as $cartProduct) {
            $productsById[$cartProduct->id] = null;
        }

        $productListQuery = null;
        if ((bool)$productsById) {
            $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $regionId);
            $curl->prepare($productListQuery);
        }

        $curl->execute();

        // корзина из ядра
        $cart = $cartRepository->getObjectByQuery($cartItemQuery);

        // товары
        if ($productListQuery) {
            $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);
        }

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart);

        // пользователь
        $user = $userItemQuery ? (new \EnterRepository\User())->getObjectByQuery($userItemQuery) : null;

        $page = new Page();
        // пользователь, корзина
        $widget = (new Repository\Partial\UserBlock())->getObject($cart, $user);
        $page->widgets['.' . $widget->widgetId] = $widget;

        $widget = (new Repository\Partial\Cart())->getObject($cart, array_values($productsById));
        $page->widgets['.' . $widget->widgetId] = $widget;

        // response
        $response = new Http\JsonResponse([
            'result' => $page,
        ]);

        return $response;
    }
}