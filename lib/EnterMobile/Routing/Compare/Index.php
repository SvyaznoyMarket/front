<?php

namespace EnterMobile\Routing\Compare;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['Compare\\Index', 'execute'];
        $this->parameters = [];
    }
}