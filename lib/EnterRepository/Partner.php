<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Util;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterModel as Model;
use EnterQuery as Query;

class Partner {
    use ConfigTrait, LoggerTrait, CurlTrait;

    private $list = [];

    /**
     * @return array
     */
    public function getList() {
        if (!$this->list) {
            $curl = $this->getCurl();

            $query = new Query\Partner\GetTrafficSource();
            $curl->prepare($query);

            $curl->execute();

            foreach ($query->getResult() as $item) {
                if (!isset($item['token'])) continue;

                $this->list[] = $item;
            }
        }

        return $this->list;
    }

    /**
     * @return array
     */
    public function getFreeSources() {
        $return = [];

        $list = $this->getList();
        foreach ($list as $item) {
            if (isset($item['paid']) && (false === $item['paid'])) {
                $return[] = $item;
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    public function getPaidSources() {
        $return = [];

        $list = $this->getList();
        foreach ($list as $item) {
            if (isset($item['paid']) && (true === $item['paid'])) {
                $return[] = $item;
            }
        }

        return $return;
    }

    /**
     * @return string[]
     */
    public function getDefaultCookieNames() {
        return [
            ['name' => 'utm_source'],
            ['name' => 'utm_content'],
            ['name' => 'utm_term'],
        ];
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
            if (!isset($item['matches'])) {
                $this->getLogger()->push(['type' => 'error', 'error' => 'Не указан match', 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);;
                continue;
            }

            $isMatched = false;
            if ($item['matches']) {
                $isMatched = true;
                foreach ($item['matches'] as $match) {
                    $k = @$match['key'];
                    $v = @$match['value'];

                    if (null === $v) {
                        $isMatched = $isMatched && isset($request->query[$k]);
                    } else if ($k) {
                        $isMatched = $isMatched && (0 === strpos($request->query[$k], $v));
                    }
                }
            }

            if ($isMatched) {
                $partner = new Model\Partner($item);

                $cookies = array_merge($this->getDefaultCookieNames(), (isset($item['cookies']) && is_array($item['cookies'])) ? (array)$item['cookies'] : []);
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
            foreach ($this->getList() as $item) {
                $iPartnerToken = $item['token'];

                if ($partnerToken !== $iPartnerToken) continue;

                $item['token'] = $partnerToken;
                $partner = new Model\Partner($item);

                $cookies = array_replace_recursive($this->getDefaultCookieNames(), (isset($item['cookies']) && is_array(isset($item['cookies']))) ? $item['cookies'] : []);
                foreach ($cookies as $cookie) {
                    if (!empty($cookie['name'])) continue;

                    if ($cookieValue = $request->cookies[$cookie['name']]) {
                        $partner->cookie[$cookie['name']] = $cookieValue;
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
        if ($partner) $logger->push(['type' => 'info', 'message' => 'Обнаружен партнер', 'source' => 'http.request.query', 'partner' => $partner->token, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);

        // если партнер НЕ обнаружен в http.request.query (или, в GET-параметрах)
        if (!$partner) {
            // получение партнера по http.request.cookie
            $partner = $this->getObjectByHttpRequestCookie($request);
            if ($partner) $logger->push(['type' => 'info', 'message' => 'Обнаружен партнер', 'source' => 'http.request.cookie', 'partner' => $partner->token, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
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
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'partner' => $partner->token, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }
    }
}