<?php

namespace EnterTerminal\Controller\Cart;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\SessionTrait;
use EnterTerminal\Controller;

class SetProductList {
    use ConfigTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

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
        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        // response
        return (new Controller\Cart())->execute($request);
    }
}
