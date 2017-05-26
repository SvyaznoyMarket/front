<?php

namespace EnterMobile\Routing\Order\Payment;

use EnterMobile\Routing\Route;

class Start extends Route {
    public function __construct() {
        $this->action = ['Order\\Payment\\Start', 'execute'];
        $this->parameters = [];
    }
}