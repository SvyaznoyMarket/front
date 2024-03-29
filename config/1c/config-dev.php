<?php

return function(Enter1C\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/config.php';
    $handler($config);

    // dev config
    $config->eventService->url = 'http://event.ent3.ru/';
};