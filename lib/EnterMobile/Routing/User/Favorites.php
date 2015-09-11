<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Favorites extends Route {
    public function __construct() {
        $this->action = ['User\\Favorites', 'execute'];
        $this->parameters = [];
    }
}