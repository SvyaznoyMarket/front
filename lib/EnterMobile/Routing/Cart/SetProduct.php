<?php

namespace EnterMobile\Routing\Cart;

use EnterMobile\Routing\Route;

class SetProduct extends Route {
    /**
     * @param string $productId
     */
    public function __construct($productId) {
        $this->action = ['Cart\\SetProduct', 'execute'];
        $this->parameters = [
            'productId' => $productId,
        ];
    }
}