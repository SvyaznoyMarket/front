<?php

namespace EnterAggregator;

use Enter\Http;

trait SessionTrait {
    /**
     * @return Http\Session
     */
    protected function getSession() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getSession();
    }
}