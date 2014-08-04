<?php

return function(\EnterMobile\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // dev config
    $config->mustacheRenderer->checkEscape = true;

    $config->debugLevel = 1;

    //$config->googleAnalitics->enabled = false;

};