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

        $query = $sellerUi ? new Query\Seller\GetItemByUi($sellerUi) : new Query\Seller\GetList();
        $curl->prepare($query)->execute();

        $result = $query->getResult();
        if ($sellerUi && $result) {
            $result = [$result]; // TODO вынести в отдельный контроллер
        }

        return new Http\JsonResponse($result);
    }
}