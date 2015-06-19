<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;

class SetProductList {
    use ConfigTrait, SessionTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $curl = $this->getCurl();
        $cartRepository = new \EnterRepository\Cart();

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // товара для корзины
        $cartProducts = $cartRepository->getProductObjectListByHttpRequest($request);
        if (!$cartProducts) {
            throw new \Exception('Товары не получены', Http\Response::STATUS_BAD_REQUEST);
        }

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        $productsById = [];
        foreach ($cartProducts as $cartProduct) {
            $productsById[$cartProduct->id] = null;
        }

        if ($productsById) {
            $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $regionId);
            $curl->prepare($productListQuery);
            $curl->execute();

            $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);
        }

        // добавление товара в корзину
        foreach ($cartProducts as $cartProduct) {
            $cartProduct->ui = $productsById[$cartProduct->id]->ui;
            $cartRepository->setProductForObject($cart, $cartProduct);
        }

        $cart->cacheId++;

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        // response
        return new Http\JsonResponse([]);
    }
}
