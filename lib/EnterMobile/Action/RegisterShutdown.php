<?php

namespace EnterMobile\Action;

use Enter\Http;
use EnterAggregator\LoggerTrait;
use EnterMobile\Action;
use EnterMobile\Controller;

class RegisterShutdown {
    use LoggerTrait;

    /**
     * @param Http\Request|null $request
     * @param Http\Response|null $response
     * @param \Exception|null $error
     * @param float $startAt
     */
    public function execute(Http\Request &$request = null, Http\Response &$response = null, &$error = null, $startAt = null) {
        register_shutdown_function(function () use (&$request, &$response, &$error, $startAt) {
            if (!$response instanceof Http\Response) {
                $response = new Http\Response();
            }

            $lastError = error_get_last();
            if ($lastError && (error_reporting() & $lastError['type'])) {
                $response = (new Controller\Error\InternalServerError())->execute($request);
                $this->getLogger()->push(['type' => 'error', 'error' => $lastError, 'tag' => ['fatal']]);
            }

            if ($error) {
                $response->statusCode = Http\Response::STATUS_INTERNAL_SERVER_ERROR;
                $this->getLogger()->push(['type' => 'error', 'error' => $error, 'tag' => ['critical']]);
            }

            // logger
            (new \EnterAggregator\Action\DumpLogger())->execute();

            $endAt = microtime(true);

            // debug info
            (new \EnterAggregator\Action\Debug())->execute($request, $response, $error, $startAt, $endAt);

            // send response
            $response->send();
        });
    }
}