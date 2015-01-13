<?php

namespace EnterMobile\Routing\Region;

use EnterMobile\Routing\Route;

class SetByName extends Route {
    public function __construct() {
        $this->action = ['Region\\Set', 'execute'];
        $this->parameters = [];
    }
}