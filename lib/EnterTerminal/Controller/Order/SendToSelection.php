<?php

namespace EnterTerminal\Controller\Order;

use Enter\Http;
use EnterTerminal\ConfigTrait;
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

        $responseData = [];

        try {
            $responseData = $sendQuery->getResult();
        } catch (\Exception $e) {
            if ($e instanceof Query\CoreQueryException) {
                $responseData['error'] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
            } else {
                $responseData['error'] = ['code' => 500, 'message' => 'Ошибка'];
            }

            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
        }

        return new Http\JsonResponse($responseData);
    }
}
