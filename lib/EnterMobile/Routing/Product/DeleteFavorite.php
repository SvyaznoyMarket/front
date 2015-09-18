<?php

namespace EnterMobile\Routing\Product;

use EnterMobile\Routing\Route;

class DeleteFavorite extends Route {
    public function __construct() {
        $this->action = ['Product\\DeleteFavorite', 'execute'];
        $this->parameters = [];
    }
}