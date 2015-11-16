<?php

namespace EnterMobileApplication\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;

class Seller {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        return new Http\JsonResponse([
            'seller' => null,
        ]);
    }
}