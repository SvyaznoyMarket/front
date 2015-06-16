<?php

namespace EnterMobile\Repository;

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
     * @return \EnterQuery\User\GetItemByToken|null
     */
    public function getQueryByHttpRequest(Http\Request $request) {
        $userToken = $this->getTokenByHttpRequest($request);
        return $userToken ? new \EnterQuery\User\GetItemByToken($userToken) : null;
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
     * @throws \Exception
     * @return Model\User|null
     */
    public function getObjectByQuery(Query $query = null) {
        if (!$query) {
            return null;
        }

        try {
            return (new \EnterRepository\User())->getObjectByQuery($query);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
            return null;
        }
    }
}