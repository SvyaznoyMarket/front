<?php

namespace Enter1C;

use EnterAggregator\Service as BaseService;
use Enter1C\Config;

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

            if ($instance->editable && $instance->cacheDir) {
                $instance = $this->loadConfigFromJsonFile($instance);
            }
        }

        return $instance;
    }
}