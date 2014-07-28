<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterMobile\Controller;
use EnterAggregator\SessionTrait;
use EnterMobile\Repository;

class Index {
    use ConfigTrait, LoggerTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $url = strtr($request->getSchemeAndHttpHost(), [
            'm.'    => '',
            ':8080' => '', //FIXME: костыль для nginx-а
        ]) . '/orders/new';

        return (new Controller\Redirect())->execute($url, 302);
    }
}