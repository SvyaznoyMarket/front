<?php

namespace EnterSite\Action;

use Enter\Util;
use EnterSite\ConfigTrait;

class LoadCachedConfig {
    use ConfigTrait;

    public function execute($configFile) {
        // cache
        $GLOBALS['EnterSite\ConfigTrait::getConfig'] = Util\Json::toObject(file_get_contents($configFile));

        $config = $this->getConfig();

        $config->requestId = uniqid();
    }
}