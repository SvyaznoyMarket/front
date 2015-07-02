<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class ChangePassword extends Route {
    public function __construct() {
        $this->action = ['User\\ChangePassword', 'execute'];
        $this->parameters = [];
    }
}