<?php

namespace EnterMobileApplication\Controller\Region;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterMobileApplication\ConfigTrait;
use EnterQuery as Query;

class ShopList {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        return new Http\JsonResponse([
            'regions' => [],
        ]);
    }
}