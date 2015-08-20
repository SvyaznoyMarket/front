<?php

namespace EnterMobileApplication\Controller;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

class Search {
    use ProductListingTrait;
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        return new Http\JsonResponse([
            'searchPhrase' => '',
            'forcedMean'   => false,
            'productCount' => 0,
            'products'     => [],
            'filters'      => [],
            'sortings'     => [],
        ]);
    }
}
