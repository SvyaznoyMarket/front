<?php

return function(\EnterMobile\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // dev config
    $config->cacheDir = (sys_get_temp_dir() ?: '/tmp') . '/' . $config->hostname;
    $config->mustacheRenderer->checkEscape = true;
    $config->debugLevel = 1;
    $config->hostname = 'm.tt.ent3.ru';

    //$config->googleAnalitics->enabled = false;

};