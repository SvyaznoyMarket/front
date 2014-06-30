<?php

namespace EnterSite\Routing\User;

use EnterSite\Routing\Route;

class Register extends Route {
    public function __construct() {
        $this->action = ['User\\Register', 'execute'];
        $this->parameters = [];
    }
}