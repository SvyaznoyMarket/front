<?php

namespace EnterTerminal\Controller\User;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;

class SendShopContact {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $shopId = is_string($request->query['shopId']) ? $request->query['shopId'] : null;
        if (!$shopId) {
            throw new \Exception('Не передан параметр shopId', Http\Response::STATUS_BAD_REQUEST);
        }
        $email = is_string($request->query['email']) ? $request->query['email'] : null;
        if (!$email) {
            throw new \Exception('Не передан параметр email', Http\Response::STATUS_BAD_REQUEST);
        }

        $sendQuery = new Query\User\SendShopContact($shopId, $email);
        $sendQuery->setTimeout(3 * $config->coreService->timeout);
        $curl->prepare($sendQuery)->execute();

        $result = $sendQuery->getResult();

        return new Http\JsonResponse($result);
    }
}