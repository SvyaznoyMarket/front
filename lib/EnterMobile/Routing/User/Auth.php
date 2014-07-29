<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Auth extends Route {
    public function __construct() {
        $this->action = ['User\\Auth', 'execute'];
        $this->parameters = [];
    }
}