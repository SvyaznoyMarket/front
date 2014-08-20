<?php

namespace EnterTerminal\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;

class Seller {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        $sellerUi = is_string($request->query['ui']) ? $request->query['ui'] : null;

        $query = new Query\Seller\GetList($sellerUi);
        $curl->prepare($query);
        $curl->execute();

        return new Http\JsonResponse($query->getResult());
    }
}