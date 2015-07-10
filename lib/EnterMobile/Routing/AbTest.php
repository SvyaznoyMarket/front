<?php

namespace EnterMobile\Routing;

use EnterMobile\Routing\Route;

class AbTest extends Route {
    public function __construct() {
        $this->action = ['AbTest', 'execute'];
        $this->parameters = [];
    }
}