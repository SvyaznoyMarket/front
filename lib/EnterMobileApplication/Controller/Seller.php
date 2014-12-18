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
        $curl = $this->getCurl();

        $sellerUi = is_string($request->query['ui']) ? $request->query['ui'] : null;
        if (!$sellerUi) {
            throw new \Exception('Не передан sellerUi', Http\Response::STATUS_BAD_REQUEST);
        }

        $response = [
            'seller' => null,
        ];

        $itemQuery = new Query\Seller\GetItemByUi($sellerUi);
        $curl->prepare($itemQuery)->execute();

        if ($item = $itemQuery->getResult()) {
            $response['seller'] = new Model\Seller($item);
        }

        return new Http\JsonResponse($response);
    }
}