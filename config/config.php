<?php

return function(\EnterAggregator\Config $config) {
    mb_internal_encoding('UTF-8');
    setlocale(LC_TIME, 'ru_RU', 'ru_RU.utf8');

    $config->dir = realpath(__DIR__ . '/..');

    $config->debugLevel = 0;

    $config->editable = false; // важно!

    $config->session->name = 'enter';
    $config->session->cookieLifetime = 2592000; // 30 дней
    $config->session->cookieDomain = '.enter.ru';
    $config->session->flashKey = '_flash';

    $config->userToken->authName = '_token';

    $config->googleAnalytics->enabled = true;

    $config->region->defaultId = '14974';
    $config->region->cookieName = 'geoshop';

    $config->abTest->cookieName = 'switchMobile';
    $config->abTest->cookieDomain = '.m.enter.ru';

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

    $config->eventService->enabled = true;
    $config->eventService->url = 'http://event.enter.ru/';
    $config->eventService->timeout = 1;
    $config->eventService->clientId = 'site';

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

    $config->kladr->token = '52b04de731608f2773000000';
    $config->kladr->key = 'c20b52a7dc6f6b28023e3d8ef81b9dbdb51ff74b';
    $config->kladr->limit = 20;

    $config->cart->sessionKey = 'cart';
    $config->cart->quickSessionKey = 'quickCart';

    $config->order->splitSessionKey = 'order_split';
    $config->order->userSessionKey = 'order_user';
    $config->order->cookieName = 'last_order';
    $config->order->sessionName = 'createdOrder';
    $config->order->prepayment->enabled = true;
    $config->order->prepayment->priceLimit = 100000;
    $config->order->bonusCardSessionKey = 'order_bonusCards';
    $config->order->minSum = 990;

    $config->product->itemPerPage = 19;
    $config->product->itemsInSlider = 60;
    $config->product->supplierLabelUi = 'c06a5f82-7cbd-11e1-8cde-3c4a92f6ffb8'; // FIXME: для тестирования

    $config->productReview->enabled = true;
    $config->productReview->itemsInCard = 7;

    $config->search->minPhraseLength = 2;

    $config->credit->enabled = false;

    $config->credit->directCredit->enabled = false;
    $config->credit->directCredit->minPrice = 3000;
    $config->credit->directCredit->partnerId = '4427';

    $config->credit->kupivkredit->enabled = false;
    $config->credit->kupivkredit->partnerId = '1-178YO4Z';
    $config->credit->kupivkredit->secretPhrase = '321ewq';
    $config->credit->kupivkredit->url = 'https://kupivkredit-test-fe.tcsbank.ru/';
    $config->credit->kupivkredit->timeout = 2;

};