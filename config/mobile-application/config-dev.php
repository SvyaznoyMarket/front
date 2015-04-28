<?php

return function(EnterMobileApplication\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // dev config
    $config->cacheDir = (sys_get_temp_dir() ?: '/tmp') . '/' . $config->hostname;

    $config->eventService->url = 'http://event.ent3.ru/';
};