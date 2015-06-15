<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class User {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Http\Request $request
     * @return string|null
     */
    public function getTokenByHttpRequest(Http\Request $request) {
        return $request->cookies[$this->getConfig()->userToken->authName];
    }

    /**
     * @param $token
     * @param Http\Response $response
     */
    public function setTokenToHttpResponse($token, Http\Response $response) {
        $config = $this->getConfig();
        $cookieName = $config->userToken->authName;
        $cookieDomain = $config->session->cookieDomain;

        if ($token) {
            $response->headers->setCookie(new Http\Cookie(
                $cookieName,
                $token,
                time() + $config->session->cookieLifetime,
                '/',
                $cookieDomain,
                false,
                true
            ));
        } else {
            $response->headers->clearCookie($cookieName, '/', $cookieDomain);
        }
    }

    /**
     * @param Http\Request $request
     * @param $defaultUrl
     * @return string
     */
    public function getRedirectUrlByHttpRequest(Http\Request $request, $defaultUrl = null) {
        if (!$defaultUrl) {
            $defaultUrl = '/';
        }

        // редирект
        $url = trim((string)($request->query['redirect_to'] ?: $request->data['redirect_to']));
        if (!$url) {
            $url = $defaultUrl;
        }

        if ($request->server['HTTP_REFERER'] == $url) {
            $url = '/';
        }

        return $url;
    }

    /**
     * @param Query $query
     * @throws \Exception
     * @return Model\User|null
     */
    public function getObjectByQuery(Query $query) {
        $user = null;

        try {
            if ($item = $query->getResult()) {
                $user = new Model\User($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);

            if (402 == $e->getCode()) {
                throw new \Exception('Пользователь не авторизован', 401);
            }
        }

        return $user;
    }
}