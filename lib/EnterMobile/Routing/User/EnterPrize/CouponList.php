<?php

namespace EnterMobile\Routing\User\EnterPrize;

use EnterMobile\Routing\Route;

class CouponList extends Route {
    public function __construct() {
        $this->action = ['User\\EnterPrize\\CouponList', 'execute'];
        $this->parameters = [];
    }
}