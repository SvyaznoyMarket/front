<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;

class Category {

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
                'image'       => '',
                'hasChildren' => false,
            ],
            'productCount' => 0,
            'products'     => [],
            'filters'      => [],
            'sortings'     => [],
        ]);
    }
}
