<?php

namespace EnterMobile\Routing\ProductSlice;

use EnterMobile\Routing\Route;

class GetCategory extends Route {
    /**
     * @param string $sliceToken
     * @param string $categoryToken
     */
    public function __construct($sliceToken, $categoryToken) {
        $this->action = ['ProductCatalog\\Slice', 'execute'];
        $this->parameters = [
            'sliceToken'    => $sliceToken,
            'categoryToken' => $categoryToken,
        ];
    }
}