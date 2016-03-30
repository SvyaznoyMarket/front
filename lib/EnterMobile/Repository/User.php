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
     * @param Http\Session $session
     * @param Http\Request $request
     * @return string|null
     */
    public function getTokenBySessionAndHttpRequest(Http\Session $session, Http\Request $request) {
        $config = $this->getConfig();

        // TODO удалить данный блок получения токена пользователя из сookie через 1-2 месяца после релиза MSITE-637 и SITE-6685; удаление производить одновременно с подобным удалением в проекте SITE
        $userTokenInCookie = $request->cookies[$config->userToken->authCookieName];
        if ($userTokenInCookie) {
            $session->set($config->userToken->authSessionName, $userTokenInCookie);
        }

        return $session->get($config->userToken->authSessionName);
    }

    /**
     * @param Http\Session $session
     * @param Http\Request $request
     * @return \EnterQuery\User\GetItemByToken|null
     */
    public function getQueryBySessionAndHttpRequest(Http\Session $session, Http\Request $request) {
        $userToken = $this->getTokenBySessionAndHttpRequest($session, $request);

        return $userToken ? new \EnterQuery\User\GetItemByToken($userToken) : null;
    }

    /**
     * @param $token
     * @param Http\Session $session
     * @param Http\Response $response
     */
    public function setTokenToSessionAndHttpResponse($token, Http\Session $session, Http\Response $response) {
        $config = $this->getConfig();
        $cookieName = $config->userToken->authCookieName;
        $cookieDomain = $config->session->cookieDomain;

        if ($token) {
            $session->set($config->userToken->authSessionName, $token);
            // TODO заменить setCookie на clearCookie через 1-2 месяца после релиза MSITE-637 и SITE-6685; замену производить одновременно с подобной заменой в проекте SITE
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
            $session->remove($config->userToken->authSessionName);
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
     * @param Query|null $query
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