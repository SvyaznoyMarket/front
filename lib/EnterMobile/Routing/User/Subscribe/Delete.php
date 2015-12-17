<?php

namespace EnterMobile\Routing\User\Subscribe;

use EnterMobile\Routing\Route;

class Delete extends Route {
    public function __construct() {
        $this->action = ['User\\Subscribe\\Delete', 'execute'];
        $this->parameters = [];
    }
}