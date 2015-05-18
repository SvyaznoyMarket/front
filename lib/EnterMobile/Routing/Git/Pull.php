<?php

namespace EnterMobile\Routing\Git;

use EnterMobile\Routing\Route;

class Pull extends Route {
    public function __construct() {
        $this->action = ['Git\Pull', 'execute'];
    }
}