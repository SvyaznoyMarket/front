<?php

return function(\EnterMobile\Config $config) {
    /** @var \Closure $handler */
    $handler = include __DIR__ . '/../config.php';
    $handler($config);

    $config->applicationName = 'mobile-site';

    $config->hostname = 'enter.ru';
    $config->fullHost = 'www.enter.ru';

    $config->logger->fileAppender->enabled = true;
    $config->logger->fileAppender->file = realpath($config->dir . '/../logs') . '/mobile.log';

    $config->router->classPrefix = 'EnterMobile\Routing\\';
    $config->router->routeFile = __DIR__ . '/../route.json';

    $config->coreService->clientId = 'site';

    $config->mustacheRenderer->templateDir = $config->dir . '/template';

    $config->product->itemPerPage = 19;
    $config->product->itemsInSlider = 60;

    $config->productReview->itemsInCard = 7;

    $config->siteVersionSwitcher->cookieName = 'mobile';
    $config->siteVersionSwitcher->cookieLifetime = 20 * 365 * 24 * 60 * 60;

    $config->redirectManager->enabled = true;
};