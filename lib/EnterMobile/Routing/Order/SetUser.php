<?php

namespace EnterMobile\Routing\Order;

use EnterMobile\Routing\Route;

class SetUser extends Route {
    public function __construct() {
        $this->action = ['Order\\SetUser', 'execute'];
        $this->parameters = [];
    }
}