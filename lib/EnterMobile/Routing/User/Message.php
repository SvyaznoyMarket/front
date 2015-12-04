<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Message extends Route {
    public function __construct() {
        $this->action = ['User\\Message\\Index', 'execute'];
        $this->parameters = [];
    }
}