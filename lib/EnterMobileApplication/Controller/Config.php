<?php

namespace EnterMobileApplication\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;

class Config {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        $keys = is_array($request->query['keys']) ? $request->query['keys'] : [];
        if (!(bool)$keys) {
            throw new \Exception('Не передан keys', Http\Response::STATUS_BAD_REQUEST);
        }

        $responseData = [
            'config' => [],
        ];

        $itemQuery = new Query\Config\GetListByKeys($keys);
        $curl->prepare($itemQuery)->execute();

        $responseData['config'] = $itemQuery->getResult();

        return new Http\JsonResponse($responseData);
    }
}