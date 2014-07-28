<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['User\\Index', 'execute'];
        $this->parameters = [];
    }
}