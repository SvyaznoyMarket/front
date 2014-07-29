<?php

namespace EnterMobile\Routing;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['Index', 'execute'];
        $this->parameters = [];
    }
}