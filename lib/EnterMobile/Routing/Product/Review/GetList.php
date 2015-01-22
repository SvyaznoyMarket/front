<?php

namespace EnterMobile\Routing\Product\Review;

use EnterMobile\Routing\Route;

class GetList extends Route {
    /**
     * @param string $productId
     */
    public function __construct($productId) {
        $this->action = ['Product\\ReviewList', 'execute'];
        $this->parameters = [
            'productId' => $productId,
        ];
    }
}