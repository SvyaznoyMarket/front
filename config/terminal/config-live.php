<?php

return function(EnterTerminal\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // live config
    $config->editable = false; // важно!
    $config->cacheDir = (sys_get_temp_dir() ?: '/tmp') . '/' . $config->hostname;
};