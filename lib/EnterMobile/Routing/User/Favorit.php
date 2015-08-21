<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Favorit extends Route {
    public function __construct() {
        $this->action = ['User\\Favorit', 'execute'];
        $this->parameters = [];
    }
}