<?php

namespace EnterMobile\Routing\Product;

use EnterMobile\Routing\Route;

class AddToFavorite extends Route {
    public function __construct() {
        $this->action = ['Product\\AddToFavorite', 'execute'];
        $this->parameters = [];
    }
}