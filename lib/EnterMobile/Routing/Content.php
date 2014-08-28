<?php

namespace EnterMobile\Routing;

use EnterMobile\Routing\Route;

class Content extends Route {
    /**
     * @param string $contentToken
     */
    public function __construct($contentToken) {
        $this->action = ['Content', 'execute'];
        $this->parameters = [
            'contentToken' => $contentToken,
        ];
    }
}