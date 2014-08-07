<?php

return function(Enter1C\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config-dev.php';
    $handler($config);

    // local config
    $config->hostname = 't.enter.loc';

    //$config->coreService->url = 'http://stierus.core.ent3.ru/';
};