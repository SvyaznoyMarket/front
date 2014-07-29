<?php

namespace EnterMobile\Routing;

use EnterMobile\Routing\Route;

class Log extends Route {
    /**
     * @param $id
     */
    public function __construct($id) {
        $this->action = ['Log', 'execute'];
        $this->parameters = [
            'id' => $id,
        ];
    }
}