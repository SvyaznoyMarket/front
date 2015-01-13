<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Register extends Route {
    public function __construct() {
        $this->action = ['User\\Register', 'execute'];
        $this->parameters = [];
    }
}