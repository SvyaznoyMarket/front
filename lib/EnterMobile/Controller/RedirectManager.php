<?php

namespace EnterMobile\Controller;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterQuery as Query;

class RedirectManager {
    use ConfigTrait, CurlTrait, LoggerTrait;

    /**
     * @param Http\Request $request
     * @param Http\Response $response
     */
    public function execute(Http\Request $request, Http\Response &$response = null) {
        if (!$this->getConfig()->redirectManager->enabled) {
            return;
        }

        if ($request->isXmlHttpRequest()) {
            return;
        }

        $url = $request->getPathInfo();

        if ('/' === $url) {
            return;
        }

        $curl = $this->getCurl();

        $query = new Query\RedirectManager\GetItem($url);
        $curl->prepare($query);
        $curl->execute();

        if ($query->getError()) {
            return;
        }

        $result = (array)$query->getResult() + ['to_url' => null];
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

        $statusCode = Http\Response::STATUS_MOVED_PERMANENTLY;
        $redirectUrl = $request->getSchemeAndHttpHost() . $redirectUrl;

        $this->getLogger()->push(['redirect' => [
            'url'   => $redirectUrl,
            'code'  => $statusCode,
        ], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['request']]);

        $response = (new \EnterMobile\Controller\Redirect())->execute($redirectUrl, $statusCode);
    }
}