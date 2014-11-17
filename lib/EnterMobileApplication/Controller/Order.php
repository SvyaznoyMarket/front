<?php

namespace EnterMobileApplication\Controller;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterRepository as Repository;
use EnterQuery as Query;
use EnterModel as Model;

class Order {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $config = $this->getConfig();

        $accessToken = is_string($request->query['accessToken']) ? $request->query['accessToken'] : null;

        $response = [
            'order' => null,
        ];

        $itemQuery = new Query\Order\GetItemByAccessToken($accessToken);
        $itemQuery->setTimeout(2 * $config->coreService->timeout);
        $curl->prepare($itemQuery)->execute();

        $response['order'] = (new Repository\Order())->getObjectByQuery($itemQuery);

        return new Http\JsonResponse($response);
    }
}