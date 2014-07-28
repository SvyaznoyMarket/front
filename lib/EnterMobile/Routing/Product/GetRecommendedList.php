<?php

namespace EnterMobile\Routing\Product;

use EnterMobile\Routing\Route;

class GetRecommendedList extends Route {
    /**
     * @param string $productId
     */
    public function __construct($productId) {
        $this->action = ['Product\\RecommendedList', 'execute'];
        $this->parameters = [
            'productId' => $productId,
        ];
    }
}