<?php

namespace EnterMobile\Routing\Order;

use EnterMobile\Routing\Route;

class Delivery extends Route {
    public function __construct() {
        $this->action = ['Order\\Delivery', 'execute'];
        $this->parameters = [];
    }
}