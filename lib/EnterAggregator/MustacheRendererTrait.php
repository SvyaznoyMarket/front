<?php

namespace EnterAggregator;

use Mustache_Engine;

trait MustacheRendererTrait {
    /**
     * @return Mustache_Engine
     */
    protected function getRenderer() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getMustacheRenderer();
    }
}