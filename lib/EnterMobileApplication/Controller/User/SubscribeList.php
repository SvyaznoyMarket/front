<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller\User\SubscribeList\Response;

    class SubscribeList {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            //$session = $this->getSession();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token');
            }
            $response->token = $token;

            // подготовка списка каналов подписок
            $subscribeChannelListQuery = new Query\Subscribe\Channel\GetList();
            $subscribeChannelListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($subscribeChannelListQuery);

            // подготовка подписок пользователя
            $subscribeListQuery = new Query\Subscribe\GetListByUserToken($token);
            $subscribeListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($subscribeListQuery);

            $curl->execute();

            try {
                // каналы подписок
                $response->subscribeChannels = (new \EnterRepository\Subscribe\Channel())->getObjectListByQuery($subscribeChannelListQuery);

                // подписки пользователя
                $response->subscribes = (new \EnterRepository\Subscribe())->getObjectListByQuery($subscribeListQuery);
            } catch (\Exception $e) {
                // костыль для ядра
                if (402 == $e->getCode()) {
                    $e = new \Exception('Пользователь неавторизован', 401);
                }

                throw $e;
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\SubscribeList {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var Model\Subscribe[] */
        public $subscribes = [];
        /** @var Model\Subscribe\Channel[] */
        public $subscribeChannels = [];
    }
}