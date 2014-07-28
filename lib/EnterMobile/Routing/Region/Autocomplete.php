<?php

namespace EnterMobile\Routing\Region;

use EnterMobile\Routing\Route;

class Autocomplete extends Route {
    public function __construct() {
        $this->action = ['Region\\Autocomplete', 'execute'];
        $this->parameters = [];
    }
}