<?php

namespace EnterMobile\Routing\ProductSet;

use EnterMobile\Routing\Route;

class Index extends Route {
    /**
     * @param string $productBarcodes
     */
    public function __construct($productBarcodes) {
        $this->action = ['ProductSet\\Index', 'execute'];
        $this->parameters = [
            'productBarcodes' => $productBarcodes,
        ];
    }
}