<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Order extends Route {
    public function __construct($orderId) {
        $this->action = ['User\\Order', 'execute'];
        $this->parameters = [
            'orderId' => $orderId,
        ];
    }
}