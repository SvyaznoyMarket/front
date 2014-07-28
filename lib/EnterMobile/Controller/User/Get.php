<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\DebugContainerTrait;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Repository;
use EnterMobile\Model\Page\User\Get as Page;
use EnterMobile\Routing;

class Get {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, RouterTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $curl = $this->getCurl();
        $cartRepository = new \EnterRepository\Cart();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session);

        // токен пользователя
        $userToken = (new \EnterRepository\User)->getTokenByHttpRequest($request);

        // запрос пользователя
        $userItemQuery = $userToken ? new Query\User\GetItemByToken($userToken) : null;
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $productsById = [];
        if ((bool)$cart->product) {
            foreach ($cart->product as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }
        }

        $productListQuery = null;
        if ((bool)$cart->product) {
            $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $regionId);
            $curl->prepare($productListQuery);
        }

        $cartItemQuery = null;
        if ((bool)$cart->product) {
            $cartItemQuery = new Query\Cart\GetItem($cart, $regionId);
            $curl->prepare($cartItemQuery);
        }

        $curl->execute();

        $user = $userItemQuery ? (new \EnterRepository\User())->getObjectByQuery($userItemQuery) : null;

        if ($productListQuery) {
            $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);
        }

        // корзина из ядра
        if ($cartItemQuery) {
            $cart = $cartRepository->getObjectByQuery($cartItemQuery);
        }

        // страница
        $page = new Page();

        // пользователь
        $page->user->sessionId = $session->getId();
        $page->user->id = $user ? $user->id : null;

        $userBlock = (new Repository\Partial\UserBlock())->getObject($cart, $user);
        $page->widgets['.' . $userBlock->widgetId] = $userBlock;

        foreach ($cart->product as $cartProduct) {
            $product = !empty($productsById[$cartProduct->id])
                ? $productsById[$cartProduct->id]
                : new \EnterModel\Product([
                    'id' => $cartProduct->id,
                ]);

            $pageCartProduct = new Page\Cart\Product();
            $pageCartProduct->id = $product->id;
            $pageCartProduct->name = $product->name;
            $pageCartProduct->price = $cartProduct->price;
            $pageCartProduct->quantity = $cartProduct->quantity;
            $page->cart->products[] = $pageCartProduct;

            $widget = (new Repository\Partial\ProductCard\CartButtonBlock())->getObject($product, $cartProduct);
            $page->widgets['.' . $widget->widgetId] = $widget;

            $widget = (new Repository\Partial\Cart\ProductButton())->getObject($product, $cartProduct);
            $page->widgets['.' . $widget->widgetId] = $widget;

            $widget = (new Repository\Partial\Cart\ProductSpinner())->getObject($product, $cartProduct->quantity, true);
            $page->widgets['.' . $widget->widgetId] = $widget;
        }

        $response = new Http\JsonResponse([
            'result' => $page,
        ]);

        // FIXME: осторожно
        /*
        if (!$request->cookies[$config->session->name]) {
            $response->headers->setCookie(new Http\Cookie(
                $config->session->name,
                $session->getId(),
                time() + $config->session->cookieLifetime,
                '/',
                $config->session->cookieDomain,
                false,
                false
            ));
        }
        */

        return $response;
    }
}