<?php

namespace EnterTerminal\Controller\Order\Confirm;

use Enter\Http;
use EnterCurlQuery;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterCurlQuery as Query;

class Check {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        if (!is_scalar($request->query['phone'])) {
            throw new \Exception('Параметр phone должен быть строкой');
        }

        if (!is_scalar($request->query['code'])) {
            throw new \Exception('Параметр code должен быть строкой');
        }

        $contentItemQuery = new Query\Order\Confirm\Check($request->query['phone'], $request->query['code']);
        $curl->prepare($contentItemQuery);
        $curl->execute();

        return new Http\JsonResponse($contentItemQuery->getResult());
    }
}