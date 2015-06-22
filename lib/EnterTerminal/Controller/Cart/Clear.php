<?php

namespace EnterTerminal\Controller\Cart;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterTerminal\Controller;

class Clear {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

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

        // удаление товаров
        $cart->product = [];

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        // response
        return (new Controller\Cart())->execute($request);
    }
}