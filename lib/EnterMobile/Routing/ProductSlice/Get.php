<?php

namespace EnterMobile\Routing\ProductSlice;

use EnterMobile\Routing\Route;

class Get extends Route {
    /**
     * @param string $sliceToken
     */
    public function __construct($sliceToken) {
        $this->action = ['ProductCatalog\\Slice', 'execute'];
        $this->parameters = [
            'sliceToken' => $sliceToken,
        ];
    }
}