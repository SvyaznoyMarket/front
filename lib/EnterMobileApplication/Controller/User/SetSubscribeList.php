<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\User\SetSubscribeList\Response;

    class SetSubscribeList {
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

            $subscribeData = (array)$request->data['subscribes'];
            if (!(bool)$subscribeData) {
                throw new \Exception('Не указано свойство subscribes', 400);
            }

            /** @var Model\Subscribe[] $subscribes */
            $subscribes = [];
            foreach ($subscribeData as $i => $subscribeItem) {
                $subscribe = new Model\Subscribe();

                // channelId
                if (!array_key_exists('channelId', $subscribeItem)) {
                    throw new \Exception(sprintf('Не указано свойство channelId у %s-й подписки', $i + 1), 400);
                }
                $subscribe->channelId = (string)$subscribeItem['channelId'];

                // type
                if (!array_key_exists('type', $subscribeItem)) {
                    throw new \Exception(sprintf('Не указано свойство type у %s-й подписки', $i + 1), 400);
                }
                $subscribe->type = (string)$subscribeItem['type'];

                // email
                if (('email' == $subscribe->type) && !array_key_exists('email', $subscribeItem)) {
                    throw new \Exception(sprintf('Не указано свойство email у %s-й подписки', $i + 1), 400);
                }
                $subscribe->email = (string)$subscribeItem['email'];

                // isConfirmed
                if (!array_key_exists('isConfirmed', $subscribeItem)) {
                    throw new \Exception(sprintf('Не указано свойство isConfirmed у %s-й подписки', $i + 1), 400);
                }
                $subscribe->isConfirmed = (bool)$subscribeItem['isConfirmed'];

                $subscribes[] = $subscribe;
            }

            // подготовка сохранения подписок
            $setSubscribeQuery = new Query\Subscribe\SetListByUserToken($token, $subscribes);
            $setSubscribeQuery->setTimeout(4 * $config->coreService->timeout);
            $curl->prepare($setSubscribeQuery);

            $curl->execute();

            try {
                $setSubscribeQuery->getResult();
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

namespace EnterMobileApplication\Controller\User\SetSubscribeList {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var string[] */
        public $errors = [];
    }
}