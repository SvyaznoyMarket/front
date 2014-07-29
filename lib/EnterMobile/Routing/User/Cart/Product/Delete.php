<?php

namespace EnterMobile\Routing\User\Cart\Product; // FIXME: изменить namespace на EnterMobile\Routing\Cart

use EnterMobile\Routing\Route;

class Delete extends Route {
    public function __construct() {
        $this->action = ['User\\Cart\\DeleteProduct', 'execute'];
        $this->parameters = [];
    }
}