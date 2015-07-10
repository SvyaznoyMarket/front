<?php

namespace EnterMobile\Routing\User\Edit;

use EnterMobile\Routing\Route;

class Save extends Route {
    public function __construct() {
        $this->action = ['User\\Edit\\Save', 'execute'];
        $this->parameters = [];
    }
}