<?php

namespace EnterMobile {
    use EnterAggregator\Config as BaseConfig;

    class Config extends BaseConfig {
        /** @var string */
        public $fullHost;
        /** @var Config\SiteVersionSwitcher */
        public $siteVersionSwitcher;

        public function __construct() {
            parent::__construct();
            $this->siteVersionSwitcher = new Config\SiteVersionSwitcher();
        }
    }
}

namespace EnterMobile\Config {
    class SiteVersionSwitcher {
        /** @var string */
        public $cookieName;
        /** @var int */
        public $cookieLifetime;
    }
}
