<?php

return function(\EnterAggregator\Config $config) {
    mb_internal_encoding('UTF-8');

    $config->dir = realpath(__DIR__ . '/..');

    $config->debugLevel = 0;

    $config->editable = false; // важно!

    $config->session->name = 'enter';
    $config->session->cookieLifetime = 2592000; // 30 дней
    $config->session->cookieDomain = '.enter.ru';
    $config->session->flashKey = '_flash';

    $config->userToken->authName = '_token';

    $config->wikimart->enabled = false;
    $config->wikimart->jsUrl = 'http://checkout.wikimart.ru/js/enter/script/';

    $config->googleAnalytics->enabled = true;
    $config->googleAnalytics->id = 'UA-25485956-5';

    $config->region->defaultId = '14974';
    $config->region->cookieName = 'geoshop';

    $config->credit->cookieName = 'credit_on';

    $config->partner->cookieName = 'last_partner';
    $config->partner->cookieLifetime = 2592000; // 30 дней
    $config->partner->enabled = true;
    // actionpay
    $config->partner->service->actionpay->enabled = true;
    // criteo
    $config->partner->service->criteo->enabled = true;
    $config->partner->service->criteo->account = 10442;
    // sociomantic
    $config->partner->service->sociomantic->enabled = true;
    // google retargeting
    $config->partner->service->googleRetargeting->enabled = true;
    // cityads
    $config->partner->service->cityads->enabled = true;

    $config->curl->queryChunkSize = 30;
    $config->curl->logResponse = false;
    $config->curl->timeout = 30;
    $config->curl->retryTimeout = 0.28;
    $config->curl->retryCount = 2;

    $config->coreService->url = 'http://api.enter.ru/';
    $config->coreService->timeout = 5;
    $config->coreService->clientId = 'site';
    $config->coreService->debug = false;

    $config->corePrivateService->url = 'http://api.enter.ru/private/';
    $config->corePrivateService->user = 'Developer';
    $config->corePrivateService->password = 'dEl23sTOas';
    $config->corePrivateService->timeout = 5;
    $config->corePrivateService->clientId = 'site';
    $config->corePrivateService->debug = false;

    $config->searchService->url = 'http://search.enter.ru/';
    $config->searchService->timeout = 5;
    $config->searchService->clientId = 'site';
    $config->searchService->debug = false;

    $config->scmsService->url = 'http://scms.enter.ru/';
    $config->scmsService->timeout = 5;

    $config->crmService->url = 'http://crm.enter.ru/';
    $config->crmService->timeout = 5;
    $config->crmService->clientId = 'site';
    $config->crmService->debug = false;


    $config->cmsService->url = 'http://cms.enter.ru/';
    $config->cmsService->timeout = 5;

    $config->adminService->url = 'http://admin.enter.ru/';
    $config->adminService->timeout = 2;

    $config->reviewService->url = 'http://scms.enter.ru/reviews/';
    $config->reviewService->timeout = 2;

    $config->contentService->url = 'http://content.enter.ru/';
    $config->contentService->timeout = 2;

    $config->infoService->url = 'http://info.ent3.ru/';
    $config->infoService->timeout = 8;

    $config->retailRocketService->account = '519c7f3c0d422d0fe0ee9775';
    $config->retailRocketService->url = 'http://api.retailrocket.ru/api/';
    $config->retailRocketService->timeout = 1;

    $config->googleTagManager->enabled = true;
    $config->googleTagManager->id = 'GTM-P65PBR';

    $config->yandexMetrika->enabled = true;
    $config->yandexMetrika->id = 10503055;

    $config->mailRu->enabled = true;
    $config->mailRu->id = 2553999;

    $config->mustacheRenderer->dir = $config->dir . '/vendor/mustache';
    $config->mustacheRenderer->cacheDir = (sys_get_temp_dir() ?: '/tmp') . '/mustache-cache';
    $config->mustacheRenderer->templateClassPrefix = preg_replace('/[^\w]/', '_', $config->hostname . '_v2' . '-');
    $config->mustacheRenderer->checkEscape = false;

    $config->mediaHosts = [
        0 => 'http://fs01.enter.ru',
        1 => 'http://fs02.enter.ru',
        2 => 'http://fs03.enter.ru',
        3 => 'http://fs04.enter.ru',
        4 => 'http://fs05.enter.ru',
        5 => 'http://fs06.enter.ru',
        6 => 'http://fs07.enter.ru',
        7 => 'http://fs08.enter.ru',
        8 => 'http://fs09.enter.ru',
        9 => 'http://fs10.enter.ru',
    ];

    $config->order->splitSessionKey = 'order_split';

    $config->product->itemPerPage = 19;
    $config->product->itemsInSlider = 60;

    $config->productReview->enabled = true;
    $config->productReview->itemsInCard = 7;

    $config->productPhoto->urlPaths = [
        0 => '/1/1/60/',
        1 => '/1/1/120/',
        2 => '/1/1/163/',
        3 => '/1/1/500/',
        4 => '/1/1/2500/',
        5 => '/1/1/1500/',
    ];

    $config->search->minPhraseLength = 2;

    $config->promo->typeId = 3;
    $config->promo->urlPaths =[
        0 => '/4/1/230x302/',
        1 => '/4/1/768x302/',
        2 => '/4/1/920x320/',
    ];

    $config->productLabel->urlPaths = [
        0 => '/7/1/66x23/',
        1 => '/7/1/124x38/',
    ];

    $config->credit->directCredit->enabled = true;
    $config->credit->directCredit->minPrice = 3000;
    $config->credit->directCredit->partnerId = '4427';

    $config->credit->kupivkredit->enabled = true;
    $config->credit->kupivkredit->partnerId = '1-178YO4Z';
    $config->credit->kupivkredit->secretPhrase = '321ewq';
    $config->credit->kupivkredit->url = 'https://kupivkredit-test-fe.tcsbank.ru/';
    $config->credit->kupivkredit->timeout = 2;

};