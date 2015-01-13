<?php

namespace EnterMobileApplication\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;

class SellerList {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        $response = [
            'sellers' => [],
        ];

        $listQuery = new Query\Seller\GetList();
        $curl->prepare($listQuery)->execute();

        foreach ($listQuery->getResult() as $item) {
            $response['sellers'][] = new Model\Seller($item);
        }

        return new Http\JsonResponse($response);
    }
}