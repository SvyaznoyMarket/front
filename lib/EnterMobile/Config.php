<?php

namespace EnterMobile {
    use EnterAggregator\Config as BaseConfig;

    class Config extends BaseConfig {
        /** @var string */
        public $fullHost;
        /** @var Config\SiteVersionSwitcher */
        public $siteVersionSwitcher;
        /** @var Config\RedirectManager */
        public $redirectManager;

        public function __construct() {
            parent::__construct();
            $this->siteVersionSwitcher = new Config\SiteVersionSwitcher();
            $this->redirectManager = new Config\RedirectManager();
        }
    }
}

namespace EnterMobile\Config {
    class SiteVersionSwitcher {
        /** @var string|null */
        public $cookieName;
        /** @var int|null */
        public $cookieLifetime;
    }

    class RedirectManager {
        /** @var bool|null */
        public $enabled;
    }
}
