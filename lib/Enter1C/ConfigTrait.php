<?php

namespace Enter1C;

use Enter1C\Config;

trait ConfigTrait {
    /**
     * @return Config
     */
    protected function getConfig() {
        /** @var Service $service */
        $service = $GLOBALS['enter.service'];

        return $service->getConfig();
    }
}