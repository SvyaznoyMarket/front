<?php

namespace EnterMobile\Routing\Order\Quick;

use EnterMobile\Routing\Route;

class Index extends Route {
    public function __construct() {
        $this->action = ['Order\\Quick\\Index', 'execute'];
        $this->parameters = [];
    }
}