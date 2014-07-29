<?php

namespace EnterMobile\Routing\Shop;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['Shop\\Index', 'execute'];
        $this->parameters = [];
    }
}