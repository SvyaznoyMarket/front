<?php

return function(\EnterMobile\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config-dev.php';
    $handler($config);

    $config->hostname = 'enter.loc';

    // local config
    $config->curl->logResponse = true;
};