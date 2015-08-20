<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\Model\Context;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;

class Slice {
    use Controller\ProductListingTrait;
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        return new Http\JsonResponse([
            'slice'        => [
                'token' => '',
                'name'  => '',
            ],
            'category'     => null,
            'categories'   => [],
            'productCount' => 0,
            'products' => [],
            'filters' => [],
            'sortings' => [],
        ]);
    }
}