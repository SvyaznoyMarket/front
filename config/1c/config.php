<?php

return function(Enter1C\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/../config.php';
    $handler($config);

    ini_set('session.use_cookies', false);
    ini_set('session.use_only_cookies', false);
    ini_set('session.use_trans_sid', true);

    $config->applicationTags = ['1capi'];

    $config->hostname = '1c.enter.ru';

    $config->logger->fileAppender->file = realpath($config->dir . '/../logs') . '/1c.log';
    $config->logger->fileAppender->enabled = true;

    $config->session->name = 'clientId';
    $config->session->cookieDomain = null;

    $config->region->cookieName = null;

    //$config->coreService->clientId = '1c';
    $config->coreService->clientId = 'site';
    $config->coreService->timeout = 60; // Иногда ядро сильно тупит (отвечая секунд за 20). Поскольку 1capi.enter.ru используют только сотрудники Энтер, то лучше уж чтобы ответ хоть долго, но выполнялся

    // TODO: убрать из настроек
    //$config->mustacheRenderer;
};