<?php

namespace EnterMobile\Routing\Order;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['Order\\Index', 'execute'];
        $this->parameters = [];
    }
}