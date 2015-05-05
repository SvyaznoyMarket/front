<?php

namespace EnterMobileApplication\Action;

use Enter\Http;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RequestIdTrait;
use EnterAggregator\Action;
use EnterMobileApplication\Controller;

class RegisterShutdown {
    use LoggerTrait, RequestIdTrait;

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
                $response->data['error'] = ['code' => 500, 'message' => isset($lastError['message']) ? $lastError['message'] : ''];

                $this->getLogger()->push(['type' => 'error', 'error' => $lastError, 'tag' => ['fatal']]);
            }

            if ($error) {
                //$response->statusCode = Http\Response::STATUS_INTERNAL_SERVER_ERROR;
                //$response->data['error'] = ['code' => 500, 'message' => $error->getMessage()];
                $this->getLogger()->push(['type' => 'error', 'error' => $error, 'tag' => ['critical']]);
            }

            // logger
            (new Action\DumpLogger())->execute();

            $endAt = microtime(true);

            // debug info
            (new \EnterMobileApplication\Action\Debug())->execute($request, $response, $error, $startAt, $endAt);

            // request id
            $response->data['requestId'] = $this->getRequestId();

            // json_encode options
            $response->encodeOption = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

            // send response
            $response->send();
        });
    }
}