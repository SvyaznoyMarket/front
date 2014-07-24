<?php

namespace EnterSite\Action;

use Enter\Http;
use EnterSite\Action;
use EnterSite\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;

class HandleResponse {
    use ConfigTrait, LoggerTrait, SessionTrait;

    /**
     * @param \Enter\Http\Request $request
     * @param Http\Response|null $response
     */
    public function execute(Http\Request $request, Http\Response &$response = null) {
        $config = $this->getConfig();
        $logger = $this->getLogger();

        $logger->push(['request' => [
            'uri'     => $request->getRequestUri(),
            'query'   => $request->query,
            'data'    => $request->data,
            'cookie'  => $request->cookies,
            'server'  => $request->server,
        ], 'action' => __METHOD__, 'tag' => ['request']]);

        // проверка редиректа
        $response = (new Action\CheckRedirect())->execute($request);
        // запуск контроллера
        if (!$response) {
            // controller call
            $controllerCall = (new Action\MatchRoute())->execute($request);

            // response
            $response = call_user_func($controllerCall, $request);
        }

        // партнерские куки
        if ($response) {
            try {
                (new Action\CheckPartner())->execute($request, $response);
            } catch (\Exception $e) {
                $logger->push(['type' => 'error', 'action' => __METHOD__, 'error'  => $e, 'tag' => ['partner']]);
            }
        }

        // log session
        // FIXME: осторожно, опасный код
        if (isset($GLOBALS['enter.http.session'])) {
            $logger->push(['session' => [
                'id'    => $this->getSession()->getId(),
                'value' => $this->getSession()->all(),
            ], 'action' => __METHOD__, 'tag' => ['request']]);
        }

        // debug cookie
        try {
            if (
                ($response instanceof Http\Response)
                && (
                    $request->cookies['debug'] != $config->debugLevel
                )
            ) {
                if (!$config->debugLevel) {
                    $response->headers->removeCookie('debug', '/', null);
                } else {
                    $response->headers->setCookie(new Http\Cookie(
                        'debug',
                        $config->debugLevel,
                        strtotime('+7 days' ),
                        '/',
                        null,
                        false,
                        false
                    ));
                }
            }
        } catch (\Exception $e) {
            $logger->push(['type' => 'error', 'action' => __METHOD__, 'error'  => $e]);
        }
    }
}