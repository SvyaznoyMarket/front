<?php

namespace EnterMobileApplication\Controller\Order;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;

class SendToSelection {
    use ConfigTrait, LoggerTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $orderNumber = is_scalar($request->query['orderNumber']) ? (string)$request->query['orderNumber'] : null;
        if (!$orderNumber) {
            throw new \Exception('Не передан orderNumber', Http\Response::STATUS_BAD_REQUEST);
        }

        $shopId = is_scalar($request->query['shopId']) ? (string)$request->query['shopId'] : null;
        if (!$shopId) {
            throw new \Exception('Не передан shopId', Http\Response::STATUS_BAD_REQUEST);
        }

        $sendQuery = new Query\Order\SendToSelection($orderNumber, $shopId);
        $sendQuery->setTimeout(10 * $config->coreService->timeout);
        $curl->prepare($sendQuery)->execute();

        if ($sendQuery->getError()) {
            $response = new Http\JsonResponse();
            $response->data['error'] = [
                'code'    => 500,
                'message' => 'Ошибка',
            ];

            $this->getLogger()->push(['type' => 'error', 'error' => $sendQuery->getError(), 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);

            return $response;
        }

        return new Http\JsonResponse($sendQuery->getResult());
    }
}
