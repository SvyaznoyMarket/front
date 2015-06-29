<?php

namespace EnterMobile\Routing\Order\Payment;

use EnterMobile\Routing\Route;

class GetForm extends Route {
    public function __construct() {
        $this->action = ['Order\\Payment\\GetForm', 'execute'];
        $this->parameters = [];
    }
}