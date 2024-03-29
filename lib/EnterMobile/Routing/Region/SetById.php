<?php

namespace EnterMobile\Routing\Region;

use EnterMobile\Routing\Route;

class SetById extends Route {
    /**
     * @param string $regionId
     */
    public function __construct($regionId) {
        $this->action = ['Region\\Set', 'execute'];
        $this->parameters = [
            'regionId' => $regionId,
        ];
    }
}