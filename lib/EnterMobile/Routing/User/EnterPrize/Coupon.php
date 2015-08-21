<?php

namespace EnterMobile\Routing\User\EnterPrize;

use EnterMobile\Routing\Route;

class Coupon extends Route {
    public function __construct($coupon) {
        $this->action = ['User\\EnterPrize\\Coupon', 'execute'];
        $this->parameters = [
            'coupon' => $coupon,
        ];
    }
}