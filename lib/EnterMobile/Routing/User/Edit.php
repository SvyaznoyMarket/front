<?php

namespace EnterMobile\Routing\User;

use EnterMobile\Routing\Route;

class Edit extends Route {
    public function __construct() {
        $this->action = ['User\\Edit', 'execute'];
        $this->parameters = [];
    }
}