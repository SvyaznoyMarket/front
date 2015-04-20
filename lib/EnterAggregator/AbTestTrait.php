<?php

namespace EnterAggregator;

use EnterRepository as Repository;

trait AbTestTrait {
    /**
     * @return Repository\AbTest
     */
    protected function getAbTest() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getAbTest();
    }
}