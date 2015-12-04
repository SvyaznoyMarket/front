<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Subscribe extends Route {
    public function __construct() {
        $this->action = ['User\\Subscribe\\Index', 'execute'];
        $this->parameters = [];
    }
}