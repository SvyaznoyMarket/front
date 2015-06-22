<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;

class IncreaseQuantityForProductList {
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

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        $ids = (array)$request->data['ids'];
        $uis = (array)$request->data['uis'];
        $quantity = isset($request->data['quantity']) ? (int)$request->data['quantity'] : 1;

        if (!$ids && !$uis) {
            throw new \Exception('Не переданы параметры ids и uis', Http\Response::STATUS_BAD_REQUEST);
        }

        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        foreach ($ids as $id) {
            if (isset($cart->product[$id])) {
                $cart->product[$id]->quantity += $quantity;
            }
        }

        if ($uis) {
            $uisToIds = [];
            foreach ($cart->product as $product) {
                $uisToIds[$product->ui] = $product->id;
            }

            foreach ($uis as $ui) {
                if (isset($uisToIds[$ui]) && isset($cart->product[$uisToIds[$ui]])) {
                    $cart->product[$uisToIds[$ui]]->quantity += $quantity;
                }
            }
        }

        $cart->cacheId++;

        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);
        return new Http\JsonResponse([]);
    }
}
