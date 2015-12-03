<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Address extends Route {
    public function __construct() {
        $this->action = ['User\\Address\\Index', 'execute'];
        $this->parameters = [];
    }
}