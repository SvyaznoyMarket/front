<?php

namespace EnterAggregator;

trait RequestIdTrait {
    /**
     * @return Config
     */
    protected function getRequestId() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getRequestId();
    }
}