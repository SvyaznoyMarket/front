<?php

namespace EnterMobile\Routing\Order\Payment;

use EnterMobile\Routing\Route;

class Fail extends Route {
    public function __construct() {
        $this->action = ['Order\\Payment\\Fail', 'execute'];
        $this->parameters = [];
    }
}