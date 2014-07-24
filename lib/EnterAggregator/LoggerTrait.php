<?php

namespace EnterAggregator;

use Enter\Logging;

trait LoggerTrait {
    /**
     * @return Logging\Logger
     */
    protected function getLogger() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getLogger();
    }
}