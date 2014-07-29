<?php

namespace EnterMobile\Routing\ProductCard;

use EnterMobile\Routing\Route;

class Get extends Route {
    /**
     * @param string $productPath
     */
    public function __construct($productPath) {
        $this->action = ['ProductCard', 'execute'];
        $this->parameters = [
            'productPath' => $productPath,
        ];
    }
}