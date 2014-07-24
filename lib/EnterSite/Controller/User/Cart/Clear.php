<?php

namespace EnterSite\Controller\User\Cart;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterSite\Repository;

class Clear {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

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

        // удаление товаров
        $cart->product = [];

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart);

        // response
        return new Http\JsonResponse([
            'result' => [],
        ]);
    }
}