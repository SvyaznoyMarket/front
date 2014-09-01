<?php

return function(EnterTerminal\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/../config.php';
    $handler($config);

    ini_set('session.use_cookies', false);
    ini_set('session.use_only_cookies', false);
    ini_set('session.use_trans_sid', true);

    $config->applicationName = 'terminal';

    $config->hostname = 't.enter.ru';

    $config->logger->fileAppender->file = realpath($config->dir . '/../logs') . '/terminal.log';
    $config->logger->fileAppender->enabled = true;

    $config->session->name = 'clientId';
    $config->session->cookieLifetime = 15552000;
    $config->session->cookieDomain = null;

    $config->region->cookieName = null;

    $config->coreService->clientId = 'terminal'; // переопределяется из http.request
    $config->coreService->timeout = 4;

    $config->infoService->timeout = 4;

    // TODO: убрать из настроек терминала
    //$config->mustacheRenderer;

    $config->product->itemPerPage = 19;
    $config->product->itemsInSlider = 60;

    $config->productReview->itemsInCard = 7;
};