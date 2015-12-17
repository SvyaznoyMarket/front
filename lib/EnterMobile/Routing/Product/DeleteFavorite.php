<?php

namespace EnterMobile\Routing\Product;

use EnterMobile\Routing\Route;

class DeleteFavorite extends Route {
    /**
     * @param string $productUi
     */
    public function __construct($productUi) {
        $this->action = ['Product\\DeleteFavorite', 'execute'];
        $this->parameters = [
            'productUi' => $productUi,
        ];
    }
}