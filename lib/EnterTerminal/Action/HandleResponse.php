<?php

namespace EnterTerminal\Action;

use Enter\Http;
use EnterAggregator\RequestIdTrait;
use EnterAggregator\LoggerTrait;
use EnterTerminal\ConfigTrait;
use EnterTerminal\Action;

class HandleResponse {
    use RequestIdTrait, ConfigTrait, LoggerTrait;

    /**
     * @param \Enter\Http\Request $request
     * @param Http\Response|null $response
     * @throws \Exception
     */
    public function execute(Http\Request $request, Http\Response &$response = null) {
        $config = $this->getConfig();
        $logger = $this->getLogger();

        $logger->push(['request' => [
            'uri'    => $request->getRequestUri(),
            'query'  => $request->query,
            'data'   => $request->data,
            'cookie' => $request->cookies,
            'server' => $request->server,
        ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['request']]);

        if ($request) {
            $config->clientId = is_scalar($request->query['clientId']) ? $request->query['clientId'] : null;
            if (!$config->clientId) {
                //throw new \Exception('Не указан параметр clientId'); FIXME
            }

            $config->coreService->clientId = $config->clientId;
        }

        if (!$response) {
            // controller call
            $controllerCall = (new Action\MatchRoute())->execute($request);

            // response
            $response = call_user_func($controllerCall, $request);
        }
    }
}