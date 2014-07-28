<?php

namespace EnterMobile\Routing\User\Cart;

use EnterMobile\Routing\Route;

class Clear extends Route {
    public function __construct() {
        $this->action = ['User\\Cart\Clear', 'execute'];
        $this->parameters = [];
    }
}