<?php

namespace EnterMobile\Routing\Shop;

use EnterMobile\Routing\Route;

class Map extends Route {
    public function __construct() {
        $this->action = ['Shop\\Map', 'execute'];
        $this->parameters = [];
    }
}