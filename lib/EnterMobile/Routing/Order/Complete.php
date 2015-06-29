<?php

namespace EnterMobile\Routing\Order;

use EnterMobile\Routing\Route;

class Complete extends Route {
    public function __construct() {
        $this->action = ['Order\\Complete', 'execute'];
        $this->parameters = [];
    }
}