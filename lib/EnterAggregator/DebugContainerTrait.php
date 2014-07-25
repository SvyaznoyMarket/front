<?php

namespace EnterAggregator;

use StdClass;

trait DebugContainerTrait {
    /**
     * @return StdClass
     */
    protected function getDebugContainer() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getDebugContainer();
    }
}