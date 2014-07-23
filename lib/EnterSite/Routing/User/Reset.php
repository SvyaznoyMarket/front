<?php

namespace EnterSite\Routing\User;

use EnterSite\Routing\Route;

class Reset extends Route {
    public function __construct() {
        $this->action = ['User\\Reset', 'execute'];
        $this->parameters = [];
    }
}