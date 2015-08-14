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

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        // товара для корзины
        $cartProducts = $cartRepository->getProductListByHttpRequest($request);
        if (!(bool)$cartProducts) {
            throw new \Exception('Товары не получены');
        }

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);

            $curl->execute();
        }

        // пользователь
        $user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);

        // добавление товаров в корзину
        foreach ($cartProducts as $cartProduct) {
            // если у товара есть знак количества (+|-) ...
            if (isset($cartProduct->quantitySign)) {
                /** @var \EnterModel\Cart\Product|null $existsCartProduct */
                $existsCartProduct = isset($cart->product[$cartProduct->id]) ? $cart->product[$cartProduct->id] : null;
                if ($existsCartProduct) {
                    $cartProduct->quantity = $existsCartProduct->quantity + (int)($cartProduct->quantitySign . $cartProduct->quantity);
                }
            }
        }

        // агрегирующий контроллер
        $controller = new \EnterAggregator\Controller\Cart\SetProductList();
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->session = $session;
        $controllerRequest->cart = $cart;
        $controllerRequest->cartProducts = $cartProducts;
        $controllerRequest->userUi = $user ? $user->ui : null;
        $controllerResponse = $controller->execute($controllerRequest);

        $cart = $controllerResponse->cart;
        $productsById = $controllerResponse->productsById;

        // удалить разбиение заказа
        $session->remove($config->order->splitSessionKey);

        // страница
        $page = new Page();

        foreach ($cart->product as $cartProduct) {
            $product = isset($productsById[$cartProduct->id]) ? $productsById[$cartProduct->id] : null;
            if (!$product) continue;

            // кнопка купить
            if ($widget = (new Repository\Partial\Cart\ProductButton())->getObject($product, $cartProduct)) {
                $page->widgets['.' . $widget->widgetId] = $widget;
            }

            // кнопка купить для родительского товара
            if ($cartProduct->parentId && $widget = (new Repository\Partial\Cart\ProductButton())->getObject(
                    new \EnterModel\Product(['id' => $cartProduct->parentId]),
                    new \EnterModel\Cart\Product(['id' => $cartProduct->parentId, 'quantity' => 1])
                )) {
                $page->widgets['.' . $widget->widgetId] = $widget;
            }

            // спиннер
            if ($widget = (new Repository\Partial\Cart\ProductSpinner())->getObject(
                $product,
                $cartProduct,
                false
            )) {
                $page->widgets['.' . $widget->widgetId] = $widget;
            }

            // пользователь, корзина
            if ($widget = (new Repository\Partial\UserBlock())->getObject($cart, $user)) {
                $page->widgets['.' . $widget->widgetId] = $widget;
            }

            if ($widget = (new Repository\Partial\Cart\ProductSum())->getObject($cartProduct)) {
                $page->widgets['.' . $widget->widgetId] = $widget;
            }

            if ($widget = (new Repository\Partial\Cart())->getObject($cart, array_values($productsById))) {
                $page->widgets['.' . $widget->widgetId] = $widget;
            }

            if ($widget = (new Repository\Partial\ProductCard\CartButtonBlock())->getObject($product, $cartProduct)) {
                $page->widgets['.' . $widget->widgetId] = $widget;
            }
        }

        // response
        $response = new Http\JsonResponse([
            'result' => $page, // TODO: вынести на уровень JsonPage.result
        ]);

        return $response;
    }
}