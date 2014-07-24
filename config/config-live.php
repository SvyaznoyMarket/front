<?php

return function(\EnterSite\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // live config
    $config->debugLevel = 0;
};