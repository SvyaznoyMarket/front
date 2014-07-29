<?php

namespace EnterMobile\Routing;

use EnterMobile\Routing\Route;

class Router extends Route {
    public function __construct() {
        $this->action = ['Router', 'execute'];
        $this->parameters = [];
    }
}