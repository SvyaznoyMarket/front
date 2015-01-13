<?php

namespace EnterMobile\Routing\Cart;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['Cart\\Index', 'execute'];
        $this->parameters = [];
    }
}