<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;

class DeleteProductList {
    use SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        $ids = (array)$request->data['ids'];
        $uis = (array)$request->data['uis'];
        if (!$ids && !$uis) {
            throw new \Exception('Не переданы параметры ids и uis', Http\Response::STATUS_BAD_REQUEST);
        }

        $cart = $cartRepository->getObjectByHttpSession($session);

        foreach ($ids as $id) {
            unset($cart->product[$id]);
        }

        if ($uis) {
            $uisToIds = [];
            foreach ($cart->product as $product) {
                $uisToIds[$product->ui] = $product->id;
            }

            foreach ($uis as $ui) {
                if (isset($uisToIds[$ui])) {
                    unset($cart->product[$uisToIds[$ui]]);
                }
            }
        }

        $cart->cacheId++;

        $cartRepository->saveObjectToHttpSession($session, $cart);
        return new Http\JsonResponse([]);
    }
}
