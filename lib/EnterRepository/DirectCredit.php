<?php

namespace EnterRepository;

use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class DirectCredit {
    use ConfigTrait;

    /**
     * @param $categoryToken
     * @return string
     */
    public function getTypeByCategoryToken($categoryToken) {
        return in_array($categoryToken,
            ['electronics', 'sport', 'appliances', 'do_it_yourself', 'furniture', 'household', 'jewel']
        ) ? $categoryToken : 'another';
    }

    /**
     * Deprecated
     * FIXME: использовать Query\PaymentGroup\GetList
     *
     * @param Model\Cart $cart
     * @return bool
     */
    public function isEnabledForCart(Model\Cart $cart) {
        $config = $this->getConfig();

        return $config->credit->directCredit->enabled && ($cart->sum >= $config->credit->directCredit->minPrice);
    }
}