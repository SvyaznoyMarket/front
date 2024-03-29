<?php

namespace EnterMobile\Action;

use Enter\Http;
use EnterMobile\Action;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\AbTestTrait;

class HandleResponse {
    use ConfigTrait, LoggerTrait, SessionTrait, AbTestTrait;

    /**
     * @param \Enter\Http\Request $request
     * @param Http\Response|null $response
     * @throws \Exception
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
        ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['request']]);

        try {
            // проверка редиректа
            $response = (new Action\CheckRedirect())->execute($request);
            // запуск контроллера
            if (!$response) {
                // controller call
                $controllerCall = (new Action\MatchRoute())->execute($request);

                // response
                $response = call_user_func($controllerCall, $request);
            }

            // аб-тест кука
            if ($response) {
                try {
                    $this->getAbTest()->setValueForResponse($response, $request);
                } catch (\Exception $e) {
                    $logger->push(['type' => 'error', 'sender' => __FILE__ . ' ' .  __LINE__, 'error'  => $e, 'tag' => ['abtest']]);
                }
            }

            // партнерские куки
            if ($response) {
                try {
                    (new Action\CheckPartner())->execute($request, $response);
                } catch (\Exception $e) {
                    $logger->push(['type' => 'error', 'sender' => __FILE__ . ' ' .  __LINE__, 'error'  => $e, 'tag' => ['partner']]);
                }
            }
        } catch (\Exception $e) {
            $logger->push(['request' => [
                'session' => isset($GLOBALS['enter.http.session']) ? [
                    'id'    => $this->getSession()->getId(),
                    'value' => $this->getSession()->all(),
                ] : null,
            ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['request']]);

            if (in_array($e->getCode(), [401, 403])) {
                if ($request->isXmlHttpRequest()) {
                    $response = new Http\JsonResponse([
                        'redirect' => '/login',
                        'error'    => ['code' => $e->getCode(), 'message' => $e->getMessage()],
                    ], $e->getCode());
                } else {
                    $response = (new \EnterAggregator\Controller\Redirect())->execute('/login', 302);
                }
            } else if ($request->isXmlHttpRequest() && ($e->getCode() > 300) && ($e->getCode() < 600)) {
                $response = new Http\JsonResponse([
                    'error' => ['code' => $e->getCode(), 'message' => $e->getMessage()],
                ], $e->getCode());
            } else {
                throw $e;
            }
        }

        $logger->push(['request' => [
            'session' => isset($GLOBALS['enter.http.session']) ? [
                'id'    => $this->getSession()->getId(),
                'value' => $this->getSession()->all(),
            ] : null,
        ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['request']]);

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
            $logger->push(['type' => 'error', 'sender' => __FILE__ . ' ' .  __LINE__, 'error'  => $e]);
        }
    }
}