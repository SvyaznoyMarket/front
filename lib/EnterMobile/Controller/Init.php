<?php

namespace EnterMobile\Controller;

use Enter\Http;
use EnterAggregator\AbTestTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterQuery as Query;

class Init {
    use ConfigTrait, CurlTrait, LoggerTrait, AbTestTrait;

    /**
     * @param Http\Request $request
     * @param Http\Response $response
     */
    public function execute(Http\Request $request, Http\Response &$response = null) {
        $redirectUrl = call_user_func(function() use($request) {
            $curl = $this->getCurl();

            $url = $request->getPathInfo();

            $redirectQuery = null;
            if (
                $this->getConfig()->redirectManager->enabled
                && !$request->isXmlHttpRequest()
                && ('/' !== $url)
                && (0 !== strpos($url, '/orders/'))
                && (0 !== strpos($url, '/order/'))
                && (0 !== strpos($url, '/cart'))
                && (0 !== strpos($url, '/private'))
            ) {
                $redirectQuery = new Query\RedirectManager\GetItem($url);
                $curl->prepare($redirectQuery);
            }

            $abTestQuery = new Query\AbTest\GetActiveList();
            $curl->prepare($abTestQuery);

            $curl->execute();

            try {
                $this->getAbTest()->setObjectListByQuery($abTestQuery);
                $this->getAbTest()->setValueForObjectListByHttpRequest($request);
            } catch(\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['abtest']]);
            }

            // если не было запроса на получение редиректа или произошла ошибка...
            if (!$redirectQuery || $redirectQuery->getError()) {
                return;
            }

            $result = (array)$redirectQuery->getResult() + ['to_url' => null];
            $redirectUrl = trim($result['to_url']);

            if (!$redirectUrl) {
                return;
            }

            if (0 !== strpos($redirectUrl, '/')) {
                $this->getLogger()->push([
                    'type' => 'warn',
                    'error' => sprintf('Неправильный редирект %s -> %s', $url, $redirectUrl),
                    'sender' => __FILE__ . ' ' .  __LINE__,
                    'tag' => ['redirect']
                ]);

                return;
            }

            if (false === strpos($redirectUrl, '?') && $request->getQueryString()) {
                $redirectUrl .= '?' . $request->getQueryString();
            }

            return $request->getSchemeAndHttpHost() . $redirectUrl;
        });

        // todo после продления https сертификата необходимо удалить данный код и раскомментировать код ниже
        if ($request->isSecure()) {
            if (!$redirectUrl) {
                $redirectUrl = $request->getUri();
            }

            $redirectUrl = preg_replace('/^https:/is', 'http:', $redirectUrl);

            if (!$redirectUrl) {
                return;
            }

            $this->getLogger()->push(['redirect' => [
                'url'   => $redirectUrl,
                'code'  => Http\Response::STATUS_FOUND,
            ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['request']]);

            $response = (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, Http\Response::STATUS_FOUND);
            return;
        }
//        if (!$request->isSecure()) {
//            if (!$redirectUrl) {
//                $redirectUrl = $request->getUri();
//            }
//
//            $redirectUrl = preg_replace('/^http:/is', 'https:', $redirectUrl);
//        }

        if (!$redirectUrl) {
            return;
        }

        $this->getLogger()->push(['redirect' => [
            'url'   => $redirectUrl,
            'code'  => Http\Response::STATUS_MOVED_PERMANENTLY,
        ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['request']]);

        $response = (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, Http\Response::STATUS_MOVED_PERMANENTLY);
    }
}