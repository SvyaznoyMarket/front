<?php

namespace EnterMobile\Routing\Order;

use EnterMobile\Routing\Route;

class Create extends Route {
    public function __construct() {
        $this->action = ['Order\\Create', 'execute'];
        $this->parameters = [];
    }
}