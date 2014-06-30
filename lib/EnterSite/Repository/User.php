<?php

namespace EnterSite\Repository;

use Enter\Http;
use Enter\Curl\Query;
use EnterSite\ConfigTrait;
use EnterSite\Model;

class User {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return string|null
     */
    public function getTokenByHttpRequest(Http\Request $request) {
        return $request->cookies[$this->getConfig()->userToken->authCookieName];
    }

    /**
     * @param $token
     * @param Http\Response $response
     */
    public function setTokenToHttpResponse($token, Http\Response $response) {
        $config = $this->getConfig();
        $cookieName = $config->userToken->authCookieName;
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
    public function getRedirectUrlByHttpRequest(Http\Request $request, $defaultUrl = '/') {
        // редирект
        $url = !empty($request->query['redirect_to']) ? $request->query['redirect_to'] : null;
        if (!$url) {
            $url = $defaultUrl;
        }

        return $url;
    }

    /**
     * @param Query $query
     * @return Model\User|null
     */
    public function getObjectByQuery(Query $query) {
        $user = null;

        if ($item = $query->getResult()) {
            $user = new Model\User($item);
        }

        return $user;
    }
}