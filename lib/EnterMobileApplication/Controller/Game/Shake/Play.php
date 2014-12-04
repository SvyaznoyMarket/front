<?php

namespace EnterMobileApplication\Controller\Game\Shake {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Game\Shake\Play\Response;

    class Play {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $session = $this->getSession();
            $curl = $this->getCurl();

            $sessionKey = 'game/shake';

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token');
            }

            // запрос пользователя
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            $curl->execute();

            // получение пользователя
            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            if (!$user) {
                throw new \Exception('Пользователь не авторизован', Http\Response::STATUS_UNAUTHORIZED); // FIXME
            }
            $response->token = $token;

            $sessionData = (array)$session->get($sessionKey) + ['state' => null];
            $isInitialized = !empty($sessionData['state']);

            // если не инициализирован
            if (!$isInitialized) {
                $initQuery = new Query\Game\Bandit\InitByUserUi($user->ui);
                $initQuery->setTimeout(10 * $config->crmService->timeout);

                $curl->prepare($initQuery);

                $curl->execute(null, 1); // 1 попытка

                $initResult = (array)$initQuery->getResult() + ['state' => null];
                if ('init' != $initResult['state']) {
                    throw new \Exception('Не удалось начать игру');
                }

                $session->set($sessionKey, $initResult);
            }

            $playQuery = new Query\Game\Bandit\PlayByUserUi($user->ui);
            $playQuery->setTimeout(5 * $config->crmService->timeout);

            $curl->prepare($playQuery);

            $curl->execute(null, 1); // 1 попытка

            $playResult = (array)$playQuery->getResult() + [
                'state'  => null,
                'result' => [],
            ];

            $response->state = $playResult['state'] ? (string)$playResult['state'] : null;
            if (('win' === $response->state) && !empty($playResult['result']['prizes']['coupon'])) {
                $response->prize = [
                    'type'         => $playResult['result']['prizes']['type'],
                    'couponNumber' => $playResult['result']['prizes']['coupon'],
                ];
            }


            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Game\Shake\Play {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
        /** @var string */
        public $state;
        /** @var array|null */
        public $prize;
    }
}