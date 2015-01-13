<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Login extends Route {
    public function __construct() {
        $this->action = ['User\\Login', 'execute'];
        $this->parameters = [];
    }
}