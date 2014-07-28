<?php

// start time
$startAt = microtime(true);

// application dir
$applicationDir = realpath(__DIR__ . '/..');

// environment
$environment = call_user_func(require $applicationDir . '/config/environment.php');

// response
$response = null;

// debug
$debug = call_user_func(require $applicationDir . '/config/mobile-application/debug.php');

// error reporting
call_user_func(require $applicationDir . '/config/error-report.php', $debug);

// autoload
call_user_func(require $applicationDir . '/config/autoload.php', $applicationDir);

// request
$request = new \Enter\Http\Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);

// exception
$error = null;

// config
(new \EnterMobileApplication\Action\InitService())->execute(include $applicationDir . sprintf('/config/mobile-application/config-%s.php', $environment));

// config post-handler
(new \EnterAggregator\Action\HandleConfig())->execute($environment, $debug);

// error handler
(new \EnterAggregator\Action\HandleError())->execute($error);

// shutdown handler, send response
(new \EnterMobileApplication\Action\RegisterShutdown())->execute($request, $response, $error, $startAt);

// response
(new \EnterMobileApplication\Action\HandleResponse())->execute($request, $response);
