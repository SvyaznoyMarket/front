<?php

namespace EnterSite\Routing\ProductCatalog;

use EnterSite\Routing\Route;

class GetBrandCategory extends Route {
    /**
     * @param string $categoryPath
     * @param string $brandToken
     */
    public function __construct($categoryPath, $brandToken) {
        $this->action = ['ProductCatalog\\BrandCategory', 'execute'];
        $this->parameters = [
            'categoryPath' => $categoryPath,
            'brandToken'   => $brandToken,
        ];
    }
}