<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Util;
use EnterSite\ConfigTrait;
use EnterSite\LoggerTrait;
use EnterModel as Model;

class Partner {
    use ConfigTrait, LoggerTrait {
        ConfigTrait::getConfig insteadof LoggerTrait;
    }

    /**
     * @return array
     */
    public function getList() {
        static $data = [];

        if (!(bool)$data) {
            $data = Util\Json::toArray(file_get_contents($this->getConfig()->dir . '/data/cms/v2/partner/paid-source.json'));
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function getDefaultCookieNames() {
        return ['utm_source', 'utm_content', 'utm_term'];
    }

    /**
     * Получить партнера из http.request.query (GET-параметры)
     *
     * @param Http\Request $request
     * @return Model\Partner|null
     */
    public function getObjectByHttpRequestQuery(Http\Request $request) {
        $partner = null;

        $data = $this->getList();

        foreach ($data as $partnerToken => $item) {
            if (!isset($item['match'])) {
                $this->getLogger()->push(['type' => 'error', 'error' => 'Не указан match', 'action' => __METHOD__, 'tag' => ['partner']]);;
                continue;
            }

            $isMatched = false;
            if ((bool)$item['match']) {
                $isMatched = true;
                foreach ($item['match'] as $k => $v) {
                    if (null === $v) {
                        $isMatched = $isMatched && isset($request->query[$k]);
                    } else if ($k) {
                        $isMatched = $isMatched && (0 === strpos($request->query[$k], $v));
                    }
                }
            }

            if ($isMatched) {
                $item['token'] = $partnerToken;
                $partner = new Model\Partner($item);

                $cookies = array_merge($this->getDefaultCookieNames(), isset($item['cookie']) ? (array)$item['cookie'] : []);
                foreach ($cookies as $cookieName) {
                    if ($cookieValue = $request->query[$cookieName]) {
                        $partner->cookie[$cookieName] = $cookieValue;
                    }
                }

                break;
            }
        }

        return $partner;
    }

    /**
     * Получить партнера из http.request.cookie
     *
     * @param Http\Request $request
     * @return Model\Partner|null
     */
    public function getObjectByHttpRequestCookie(Http\Request $request) {
        $partner = null;

        $partnerToken = $request->cookies[$this->getConfig()->partner->cookieName];
        if ($partnerToken) {
            foreach ($this->getList() as $iPartnerToken => $item) {
                if ($partnerToken !== $iPartnerToken) continue;

                $item['token'] = $partnerToken;
                $partner = new Model\Partner($item);

                $cookies = array_merge($this->getDefaultCookieNames(), isset($item['cookie']) ? (array)$item['cookie'] : []);
                foreach ($cookies as $cookieName) {
                    if ($cookieValue = $request->cookies[$cookieName]) {
                        $partner->cookie[$cookieName] = $cookieValue;
                    }
                }

                break;
            }
        }

        return $partner;
    }

    /**
     * @param Http\Request $request
     * @return Model\Partner|null
     */
    public function getObjectByHttpRequest(Http\Request $request) {
        $logger = $this->getLogger();

        // получение партнера по http.request.query
        $partner = $this->getObjectByHttpRequestQuery($request);
        if ($partner) $logger->push(['type' => 'info', 'message' => 'Обнаружен партнер', 'source' => 'http.request.query', 'partner' => $partner->token, 'action' => __METHOD__, 'tag' => ['partner']]);

        // если партнер НЕ обнаружен в http.request.query (или, в GET-параметрах)
        if (!$partner) {
            // получение партнера по http.request.cookie
            $partner = $this->getObjectByHttpRequestCookie($request);
            if ($partner) $logger->push(['type' => 'info', 'message' => 'Обнаружен партнер', 'source' => 'http.request.cookie', 'partner' => $partner->token, 'action' => __METHOD__, 'tag' => ['partner']]);
        }

        return $partner;
    }

    /**
     * @param Model\Partner $partner
     * @param Http\Response $response
     */
    public function setTokenToHttpResponse(Model\Partner $partner, Http\Response $response) {
        $config = $this->getConfig();

        $cookieLifetime = time() + $config->partner->cookieLifetime;
        $cookieDomain = $config->session->cookieDomain;

        // кука последнего партнера
        $response->headers->setCookie(new Http\Cookie(
            $config->partner->cookieName,
            $partner->token,
            $cookieLifetime,
            '/',
            $cookieDomain,
            false,
            true
        ));

        try {
            foreach ($partner->cookie as $cookieName => $cookieValue) {
                $response->headers->setCookie(new Http\Cookie(
                    $cookieName,
                    $cookieValue,
                    $cookieLifetime,
                    '/',
                    $cookieDomain,
                    false,
                    true
                ));
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'partner' => $partner->token, 'action' => __METHOD__, 'tag' => ['partner']]);
        }
    }
}