<?php

return function(Enter1C\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/../config.php';
    $handler($config);

    ini_set('session.use_cookies', false);
    ini_set('session.use_only_cookies', false);
    ini_set('session.use_trans_sid', true);

    $config->applicationName = '1capi';

    $config->hostname = '1c.enter.ru';

    $config->logger->fileAppender->file = realpath($config->dir . '/../logs') . '/1c.log';
    $config->logger->fileAppender->enabled = true;

    $config->session->name = 'clientId';
    $config->session->cookieDomain = null;

    $config->region->cookieName = null;

    //$config->coreService->clientId = '1c';
    $config->coreService->clientId = 'site';

    // TODO: убрать из настроек
    //$config->mustacheRenderer;
};