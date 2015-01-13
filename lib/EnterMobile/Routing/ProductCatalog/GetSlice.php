<?php

namespace EnterMobile\Routing\ProductCatalog;

use EnterMobile\Routing\Route;

class GetSlice extends Route {
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