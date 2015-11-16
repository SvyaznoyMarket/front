<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;
use EnterModel as Model;

class Category {
    use ConfigTrait, CurlTrait;
    use Controller\ProductListingTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        return new Http\JsonResponse([
            'category'     => [
                'id'          => '',
                'name'        => '',
                'media'       => ['photos' => []],
                'hasChildren' => false,
            ],
            'productCount' => 0,
            'products'     => [],
            'filters'      => [],
            'sortings'     => [],
        ]);
    }
}
