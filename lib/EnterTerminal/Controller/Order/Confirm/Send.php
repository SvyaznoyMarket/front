<?php

namespace EnterTerminal\Controller\Order\Confirm;

use Enter\Http;
use EnterQuery;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;

class Send {
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

        $contentItemQuery = new Query\Order\Confirm\Send($request->query['phone']);
        $curl->prepare($contentItemQuery);
        $curl->execute();

        return new Http\JsonResponse($contentItemQuery->getResult());
    }
}