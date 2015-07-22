<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Orders extends Route {
    public function __construct() {
        $this->action = ['User\\Orders', 'execute'];
        $this->parameters = [];
    }
}