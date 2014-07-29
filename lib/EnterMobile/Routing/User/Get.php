<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Get extends Route {
    public function __construct() {
        $this->action = ['User\\Get', 'execute'];
        $this->parameters = [];
    }
}