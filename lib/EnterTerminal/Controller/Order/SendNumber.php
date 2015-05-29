<?php

namespace EnterTerminal\Controller\Order;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;

class SendNumber {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $orderNumber = is_scalar($request->query['orderNumber']) ? $request->query['orderNumber'] : null;
        if (!$orderNumber) {
            throw new \Exception('Параметр orderNumber должен быть строкой', Http\Response::STATUS_BAD_REQUEST);
        }

        $notificationType = is_scalar($request->query['notificationType']) ? $request->query['notificationType'] : null;

        $phone = is_scalar($request->query['phone']) ? $request->query['phone'] : null;

        // TODO
        $token = null;

        $sendQuery = new Query\Order\SendNumber($orderNumber, $notificationType, $phone, $token);
        $sendQuery->setTimeout(10 * $config->coreService->timeout);
        $curl->query($sendQuery);

        $responseData = [];

        if ($sendQuery->getError()) {
            $responseData['error'] = [
                'code'    => $sendQuery->getError()->getCode(),
                'message' => $sendQuery->getError()->getMessage(),
            ];
        } else {
            $responseData = $sendQuery->getResult();
        }

        return new Http\JsonResponse($responseData);
    }
}
