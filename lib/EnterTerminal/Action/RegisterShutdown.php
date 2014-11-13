<?php

namespace EnterTerminal\Action;

use Enter\Http;
use EnterAggregator\LoggerTrait;
use EnterTerminal\Action;
use EnterTerminal\Controller;

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
                $response = new Http\JsonResponse();
            }

            $lastError = error_get_last();
            if ($lastError && (error_reporting() & $lastError['type'])) {
                //$response = (new Controller\Error\InternalServerError())->execute($request);
                $response->statusCode = Http\Response::STATUS_INTERNAL_SERVER_ERROR;
                $response->data['error'] = [
                    'code'    => 500,
                    'type'    => isset($lastError['type']) ? $lastError['type'] : null,
                    'message' => isset($lastError['message']) ? $lastError['message'] : null,
                    'file'    => isset($lastError['file']) ? $lastError['file'] : null,
                    'line'    => isset($lastError['line']) ? $lastError['line'] : null,
                ];

                $this->getLogger()->push(['type' => 'error', 'error' => $lastError, 'tag' => ['fatal']]);
            }

            if ($error) {
                //$response->statusCode = Http\Response::STATUS_INTERNAL_SERVER_ERROR;
                //$response->data['error'] = ['code' => 500, 'message' => $error->getMessage()];
                $this->getLogger()->push(['type' => 'error', 'error' => $error, 'tag' => ['critical']]);
            }

            // logger
            (new \EnterAggregator\Action\DumpLogger())->execute();

            $endAt = microtime(true);

            // debug info
            //(new \EnterAggregator\Action\Debug())->execute($request, $response, $error, $startAt, $endAt);
            (new Action\Debug())->execute($request, $response, $error, $startAt, $endAt);

            // send response
            $response->send();
        });
    }
}