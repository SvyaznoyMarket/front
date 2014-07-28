<?php

namespace EnterMobile\Routing\Promo;

use EnterMobile\Routing\Route;

class Redirect extends Route {
    /**
     * @param string $promoId
     */
    public function __construct($promoId) {
        $this->action = ['Promo\\Redirect', 'execute'];
        $this->parameters = [
            'promoId' => $promoId,
        ];
    }
}