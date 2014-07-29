<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Reset extends Route {
    public function __construct() {
        $this->action = ['User\\Reset', 'execute'];
        $this->parameters = [];
    }
}