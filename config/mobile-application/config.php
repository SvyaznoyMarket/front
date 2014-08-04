<?php

return function(EnterMobileApplication\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/../config.php';
    $handler($config);

    ini_set('session.use_cookies', false);
    ini_set('session.use_only_cookies', false);
    //ini_set('session.use_trans_sid', true);

    $config->hostname = 'mapi.enter.ru';

    $config->logger->fileAppender->file = realpath($config->dir . '/../logs') . '/mobile-application.log';
    $config->logger->fileAppender->enabled = true;

    $config->session->name = 'clientId';
    $config->session->cookieLifetime = 15552000;
    $config->session->cookieDomain = null;

    $config->userToken->authCookieName = null; // TODO: убрать из настроек mobile-application

    $config->region->cookieName = null; // TODO: убрать из настроек mobile-application

    $config->coreService->clientId = 'mobile-application'; // переопределяется из http.request

    // TODO: убрать из настроек mobile-application
    //$config->mustacheRenderer;

    $config->product->itemPerPage = 19;
    $config->product->itemsInSlider = 60;

    $config->productReview->itemsInCard = 7;
};