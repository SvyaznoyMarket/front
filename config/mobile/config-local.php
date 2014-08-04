<?php

return function(\EnterMobile\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config-dev.php';
    $handler($config);

    // local config
    $config->hostname = 'enter.loc';
    $config->session->cookieDomain = '.enter.loc';

    $config->googleAnalitics->enabled = false;
    $config->partner->enabled = false;
};