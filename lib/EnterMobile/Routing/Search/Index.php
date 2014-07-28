<?php

namespace EnterMobile\Routing\Search;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct($q) {
        $this->action = ['Search\\Index', 'execute'];
        $this->parameters = [
            'q' => $q,
        ];
    }
}