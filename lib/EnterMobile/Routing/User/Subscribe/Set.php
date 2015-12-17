<?php

namespace EnterMobile\Routing\User\Subscribe;

use EnterMobile\Routing\Route;

class Set extends Route {
    public function __construct() {
        $this->action = ['User\\Subscribe\\Set', 'execute'];
        $this->parameters = [];
    }
}