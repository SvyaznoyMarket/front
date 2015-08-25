<?php

namespace EnterMobile\Routing\Shop;

use EnterMobile\Routing\Route;

class GetCoordinates extends Route {
    public function __construct() {
        $this->action = ['Shop\\GetCoordinates', 'execute'];
        $this->parameters = [];
    }
}