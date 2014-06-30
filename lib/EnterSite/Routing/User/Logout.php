<?php

namespace EnterSite\Routing\User;

use EnterSite\Routing\Route;

class Logout extends Route {
    public function __construct() {
        $this->action = ['User\\Logout', 'execute'];
        $this->parameters = [];
    }
}