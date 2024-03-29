<?php

namespace EnterAggregator\Controller;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;

class Redirect {
    use ConfigTrait, LoggerTrait;

    public function execute($url, $statusCode) {
        $content = sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="refresh" content="0;url=%1$s" />

        <title>Redirecting to %1$s</title>
    </head>
    <body>
        Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));

        $response = new Http\Response($content, $statusCode);
        $response->headers['Location'] = $url;
        if (!$response->isRedirect()) {
            throw new \InvalidArgumentException(sprintf('Неверный код статуса %s для http-ответа', $statusCode));
        }

        return $response;
    }
}