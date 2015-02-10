<?php

namespace EnterMobile\Routing\Order\Slot;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['Order\\Slot\\Index', 'execute'];
        $this->parameters = [];
    }
}