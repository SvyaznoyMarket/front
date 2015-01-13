<?php

namespace EnterMobile\Routing\Product;

use EnterMobile\Routing\Route;

class GetListByFilter extends Route {
    public function __construct() {
        $this->action = ['Product\\ListByFilter', 'execute'];
    }
}