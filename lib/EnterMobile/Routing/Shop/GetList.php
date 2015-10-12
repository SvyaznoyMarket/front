<?php

namespace EnterMobile\Routing\Shop;

use EnterMobile\Routing\Route;

class GetList extends Route {
    public function __construct() {
        $this->action = ['Shop\\GetList', 'execute'];
        $this->parameters = [];
    }
}