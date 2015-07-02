<?php

namespace EnterMobile\Routing\User\EnterPrize;

use EnterMobile\Routing\Route;

class EnterPrizeList extends Route {
    public function __construct() {
        $this->action = ['User\\EnterPrize\\EnterPrizeList', 'execute'];
        $this->parameters = [];
    }
}