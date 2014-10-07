<?php

namespace EnterMobileApplication {
    use EnterAggregator\Config as BaseConfig;

    class Config extends BaseConfig {
        /** @var string */
        public $clientId;

        public function __construct() {
            parent::__construct();
        }
    }
}
