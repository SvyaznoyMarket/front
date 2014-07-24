<?php

namespace EnterTerminal\Controller\Order;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterCurlQuery as Query;

class SendToSelection {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        if (!is_scalar($request->query['orderNumber'])) {
            throw new \Exception('Параметр orderNumber должен быть строкой');
        }

        if (!is_scalar($request->query['shopId'])) {
            throw new \Exception('Параметр shopId должен быть строкой');
        }

        $contentItemQuery = new Query\Order\SendToSelection($request->query['orderNumber'], $request->query['shopId']);
        $curl->prepare($contentItemQuery);
        $curl->execute();

        if ($contentItemQuery->getError()) {
            $response = new Http\JsonResponse();
            $response->data['error'] = [
                'code'    => $contentItemQuery->getError()->getCode(),
                'message' => $contentItemQuery->getError()->getMessage(),
            ];

            return $response;
        }

        return new Http\JsonResponse($contentItemQuery->getResult());
    }
}
