<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

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

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        // удаление товаров
        $cart->product = [];

        $cart->cacheId++;

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        // response
        return new Http\JsonResponse([]);
    }
}