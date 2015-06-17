<?php

namespace EnterMobile\Repository;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;

class Cart {
    use ConfigTrait, CurlTrait, SessionTrait;

    /**
     * @param string $regionId
     * @return array
     */
    public function getObjectAndPreparedQueries($regionId) {
        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession());

        if ($this->getConfig()->wikimart->enabled) {
            $cart->product = [];
            $cart->sum = 0;
        }

        if ($cart->product) {
            $curl = $this->getCurl();

            $cartItemQuery = new \EnterQuery\Cart\GetItem($cart, $regionId);
            $curl->prepare($cartItemQuery);

            $cartProductsIds = [];
            foreach ($cart->product as $cartProduct) {
                $cartProductsIds[] = $cartProduct->id;
            }

            $cartProductListQuery = new \EnterQuery\Product\GetListByIdList($cartProductsIds, $regionId);
            $curl->prepare($cartProductListQuery);

            return [$cart, $cartItemQuery, $cartProductListQuery];
        } else {
            return [$cart, null, null];
        }
    }
}