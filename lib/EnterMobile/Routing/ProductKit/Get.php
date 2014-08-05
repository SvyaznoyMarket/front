<?php

namespace EnterMobile\Routing\ProductKit;

use EnterMobile\Routing\Route;

class Get extends Route {
    /**
     * @param string $productPath
     */
    public function __construct($productPath) {
        $this->action = ['Product\\KitList', 'execute'];
        $this->parameters = [
            'productPath' => $productPath,
        ];
    }
}