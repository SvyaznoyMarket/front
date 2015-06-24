<?php

namespace EnterAggregator;

use Enter\Http;

trait SessionTrait {
    /**
     * @param string|null $sessionId
     * @return Http\Session
     */
    protected function getSession($sessionId = null) {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getSession($sessionId);
    }
}