<?php

namespace EnterMobile\Routing\Product;

use EnterMobile\Routing\Route;

class QuantityAvailabilityList extends Route {
    public function __construct() {
        $this->action = ['Product\\QuantityAvailabilityList', 'execute'];
    }
}