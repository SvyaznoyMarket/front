<?php

namespace EnterMobileApplication;

use EnterMobileApplication\Config;

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