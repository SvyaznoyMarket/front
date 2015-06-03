<?php

namespace EnterMobile\Routing\Certificate;

use EnterMobile\Routing\Route;

class Check extends Route {
    public function __construct() {
        $this->action = ['Certificate\Check', 'execute'];
    }
}