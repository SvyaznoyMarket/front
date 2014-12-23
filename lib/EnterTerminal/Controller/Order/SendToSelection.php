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

        if (!is_scalar($request->query['orderNumber'])) {
            throw new \Exception('Параметр orderNumber должен быть строкой', Http\Response::STATUS_BAD_REQUEST);
        }

        if (!is_scalar($request->query['shopId'])) {
            throw new \Exception('Параметр shopId должен быть строкой', Http\Response::STATUS_BAD_REQUEST);
        }

        $contentItemQuery = new Query\Order\SendToSelection($request->query['orderNumber'], $request->query['shopId']);
        $contentItemQuery->setTimeout(10 * $config->coreService->timeout);
        $curl->prepare($contentItemQuery)->execute();

        if ($contentItemQuery->getError()) {
            $response = new Http\JsonResponse();
            $response->data['error'] = [
                'code'    => 500,
                'message' => 'Ошибка',
            ];

            $this->getLogger()->push(['type' => 'error', 'error' => $contentItemQuery->getError(), 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);

            return $response;
        }

        return new Http\JsonResponse($contentItemQuery->getResult());
    }
}
