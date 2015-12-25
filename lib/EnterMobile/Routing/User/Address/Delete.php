<?php

namespace EnterMobile\Routing\User\Address;

use EnterMobile\Routing\Route;

class Delete extends Route {
    public function __construct() {
        $this->action = ['User\\Address\\Delete', 'execute'];
        $this->parameters = [];
    }
}