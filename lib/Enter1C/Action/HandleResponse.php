<?php

namespace Enter1C\Action;

use Enter\Http;
use EnterAggregator\RequestIdTrait;
use EnterAggregator\LoggerTrait;
use Enter1C\ConfigTrait;
use Enter1C\Action;

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
        ], 'action' => __METHOD__, 'tag' => ['request']]);

        if (!$response) {
            // controller call
            $controllerCall = (new Action\MatchRoute())->execute($request);

            // response
            $response = call_user_func($controllerCall, $request);
        }
    }
}