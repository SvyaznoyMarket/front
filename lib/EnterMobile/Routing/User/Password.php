<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Password extends Route {
    public function __construct() {
        $this->action = ['User\\Password', 'execute'];
        $this->parameters = [];
    }
}