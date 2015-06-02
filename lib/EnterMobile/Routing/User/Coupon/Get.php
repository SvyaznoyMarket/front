<?php

namespace EnterMobile\Routing\User\Coupon;

use EnterMobile\Routing\Route;

class Get extends Route {
    public function __construct() {
        $this->action = ['User\\Coupon\\Get', 'execute'];
        $this->parameters = [];
    }
}