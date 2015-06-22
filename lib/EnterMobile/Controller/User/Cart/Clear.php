<?php

namespace EnterMobile\Controller\User\Cart;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterMobile\Repository;

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
        return new Http\JsonResponse([
            'result' => [],
        ]);
    }
}