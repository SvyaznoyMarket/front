<?php

namespace EnterMobile\Repository;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\CurlTrait;

class Cart {
    use ConfigTrait, CurlTrait;

    /**
     * @param string $regionId
     * @return \EnterQuery\Cart\Price\GetItem|null
     */
    public function getPreparedCartItemQuery(\EnterModel\Cart $cart, $regionId) {
        if ($cart->product) {
            $cartItemQuery = new \EnterQuery\Cart\Price\GetItem($cart, $regionId);
            $this->getCurl()->prepare($cartItemQuery);

            return $cartItemQuery;
        } else {
            return null;
        }
    }

    /**
     * @param string $regionId
     * @return \EnterQuery\Product\GetListByIdList|null
     */
    public function getPreparedCartProductListQuery(\EnterModel\Cart $cart, $regionId) {
        if ($cart->product) {
            $cartProductsIds = [];
            foreach ($cart->product as $cartProduct) {
                $cartProductsIds[] = $cartProduct->id;
            }

            $cartProductListQuery = new \EnterQuery\Product\GetListByIdList($cartProductsIds, $regionId);
            $this->getCurl()->prepare($cartProductListQuery);

            return $cartProductListQuery;
        } else {
            return null;
        }
    }
}