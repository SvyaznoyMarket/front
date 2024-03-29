<?php

namespace Enter1C\Action;

use Enter\Http;
use Enter1C\Http\XmlResponse;
use EnterAggregator\LoggerTrait;
use EnterAggregator\Action;
use Enter1C\Controller;

class RegisterShutdown {
    use LoggerTrait;

    /**
     * @param Http\Request|null $request
     * @param XmlResponse|null $response
     * @param \Exception|null $error
     * @param float $startAt
     */
    public function execute(Http\Request &$request = null, XmlResponse &$response = null, &$error = null, $startAt = null) {
        register_shutdown_function(function () use (&$request, &$response, &$error, $startAt) {
            if (!$response instanceof XmlResponse) {
                $response = new XmlResponse();
            }

            // logger
            (new Action\DumpLogger())->execute();

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

            $endAt = microtime(true);

            // debug info
            (new Action\Debug())->execute($request, $response, $error, $startAt, $endAt);

            // send response
            $response->send();
        });
    }
}