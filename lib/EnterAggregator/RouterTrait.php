<?php

namespace EnterAggregator;

use Enter\Routing;

trait RouterTrait {
    /**
     * @return Routing\Router
     */
    protected function getRouter() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getRouter();
    }
}