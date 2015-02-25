<?php

return function(\EnterMobile\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // dev config
    $config->cacheDir = (sys_get_temp_dir() ?: '/tmp') . '/' . $config->hostname;
    $config->mustacheRenderer->checkEscape = true;
    $config->debugLevel = 1;

    //$config->googleAnalitics->enabled = false;
    $config->coreService->url        = 'http://haritonov.core.ent3.ru/';
    $config->corePrivateService->url = 'http://haritonov.core.ent3.ru/private/';
    $config->coreService->timeout = 30;
    $config->corePrivateService->timeout = 30;

};