<?php

namespace EnterMobile\Routing;

use EnterMobile\Routing\Route;

class Content extends Route {
    /**
     * @param $id
     */
    public function __construct($contentToken) {
        $this->action = ['Content', 'execute'];
        $this->parameters = [
            'contentToken' => $contentToken,
        ];
    }
}