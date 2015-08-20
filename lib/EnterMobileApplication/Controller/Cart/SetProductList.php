<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;

class SetProductList {
    use SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        return new Http\JsonResponse([]);
    }
}
