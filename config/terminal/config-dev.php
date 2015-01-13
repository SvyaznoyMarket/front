<?php

return function(EnterTerminal\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // dev config
    $config->cacheDir = (sys_get_temp_dir() ?: '/tmp') . '/' . $config->hostname;
    $config->credit->kupivkredit->url = 'https://kupivkredit-test-fe.tcsbank.ru/';
};