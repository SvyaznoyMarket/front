<?php

namespace EnterMobile\Routing\ShopCard;

use EnterMobile\Routing\Route;

class Get extends Route {
    /**
     * @param string $shopToken
     */
    public function __construct($shopToken) {
        $this->action = ['ShopCard', 'execute'];
        $this->parameters = [
            'shopToken'   => $shopToken,
        ];
    }
}