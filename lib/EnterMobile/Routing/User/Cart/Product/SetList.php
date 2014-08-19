<?php

namespace EnterMobile\Routing\User\Cart\Product;

use EnterMobile\Routing\Route;

class SetList extends Route {
    public function __construct() {
        $this->action = ['User\\Cart\\SetProductList', 'execute'];
        $this->parameters = [];
    }
}