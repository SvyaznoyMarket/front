<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;

class SetProductList {
    use SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session);

        // товара для корзины
        $cartProducts = $cartRepository->getProductObjectListByHttpRequest($request);
        if (!$cartProducts) {
            throw new \Exception('Товары не получены', Http\Response::STATUS_BAD_REQUEST);
        }

        // добавление товара в корзину
        foreach ($cartProducts as $cartProduct) {
            $cartRepository->setProductForObject($cart, $cartProduct);
        }

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart);

        // response
        return (new Controller\Cart())->execute($request);
    }
}
