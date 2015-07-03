<?php

namespace EnterMobile\Routing\Search;

use EnterMobile\Routing\Route;

class Autocomplete extends Route {
    public function __construct($q) {
        $this->action = ['Search\\Autocomplete', 'execute'];
        $this->parameters = [
            'q' => $q,
        ];
    }
}