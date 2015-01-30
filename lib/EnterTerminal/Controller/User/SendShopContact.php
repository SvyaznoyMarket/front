<?php

namespace EnterTerminal\Controller\User;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterQuery as Query;

class SendShopContact {
    use ConfigTrait, CurlTrait, LoggerTrait;

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
        $isSubscribe = is_scalar($request->query['isSubscribe']) ? (bool)$request->query['isSubscribe'] : null;

        $sendQuery = new Query\User\SendShopContact($shopId, $email);
        $sendQuery->setTimeout(3 * $config->coreService->timeout);
        $curl->prepare($sendQuery);

        $subscribeQuery = null;
        if ($isSubscribe) {
            $subscribe = new \EnterModel\Subscribe();
            $subscribe->channelId = '1';
            $subscribe->email = $email;

            $subscribeQuery = new Query\Subscribe\CreateItem($subscribe);
            $subscribeQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($subscribeQuery);
        }

        $curl->execute();

        try {
            if ($subscribeQuery) {
                $subscribeQuery->getResult();
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['subscribe']]);
        }

        $result = $sendQuery->getResult();

        return new Http\JsonResponse($result);
    }
}