<?php

namespace EnterMobile\Action;

use EnterMobile\Service;

class InitService {
    /**
     * @param callable $configHandler
     * @throws \Exception
     */
    public function execute($configHandler) {
        $service = new Service($configHandler);
        $service->getConfig();

        $GLOBALS['enter.service'] = $service;
    }
}