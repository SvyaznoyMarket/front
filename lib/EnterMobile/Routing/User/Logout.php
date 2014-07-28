<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Logout extends Route {
    public function __construct() {
        $this->action = ['User\\Logout', 'execute'];
        $this->parameters = [];
    }
}