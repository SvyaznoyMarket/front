<?php

namespace EnterTerminal;

use EnterAggregator\Service as BaseService;
use EnterTerminal\Config;

class Service extends BaseService {
    /**
     * @throws \Exception
     * @return Config
     */
    public function getConfig() {
        static $instance;

        if (!$instance) {
            $instance = new Config();
            call_user_func_array($this->configHandler, [$instance]);
        }

        return $instance;
    }
}