<?php

namespace EnterMobile\Routing\Index;

use EnterMobile\Routing\Route;

class Recommendations extends Route {
    public function __construct() {
        $this->action = ['Index\\RecommendedList', 'execute'];
        $this->parameters = [];
    }
}