<?php

namespace EnterMobileApplication\Controller\Order;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;

class SendToSelection {
    use ConfigTrait, LoggerTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        return new Http\JsonResponse([]);
    }
}
