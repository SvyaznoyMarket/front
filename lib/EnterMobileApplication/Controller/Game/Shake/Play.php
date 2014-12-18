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

            $sessionData = array_merge(['state' => null, 'user' => null], (array)$session->get($sessionKey));
            $isInitialized = !empty($sessionData['state']);

            // получение пользователя
            $user = null;
            try {
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            } catch (\Exception $e) {}
            if ($user) {
                $response->token = $token;
                $sessionData['user']['uid'] = $user->ui;
            } else {
                $sessionData['user']['uid'] = $token;
            }

            // если не инициализирован
            if (!$isInitialized) {
                $initQuery = new Query\Game\Bandit\InitByUserUi($sessionData['user']['uid']);
                $initQuery->setTimeout(10 * $config->crmService->timeout);

                $curl->prepare($initQuery);

                $curl->execute(null, 1); // 1 попытка

                $initResult = (array)$initQuery->getResult() + ['state' => null, 'user' => null];
                if ('init' != $initResult['state']) {
                    throw new \Exception('Не удалось начать игру');
                }
                if (empty($initResult['user']['uid'])) {
                    throw new \Exception('Не получен идентификатор пользователя');
                }

                $sessionData = $initResult;

                $session->set($sessionKey, $sessionData);
            }

            $playQuery = new Query\Game\Bandit\PlayByUserUi($sessionData['user']['uid']);
            $playQuery->setTimeout(5 * $config->crmService->timeout);

            $curl->prepare($playQuery);

            $curl->execute(null, 1); // 1 попытка

            try {
                $playResult = (array)$playQuery->getResult() + [
                    'state'  => null,
                    'result' => [],
                ];

                $response->state = $playResult['state'] ? (string)$playResult['state'] : null;
                if (('win' === $response->state) && !empty($playResult['result']['prizes']['coupon'])) {
                    $couponSeriesId = $playResult['result']['prizes']['coupon'];

                    $seriesListQuery = new Query\Coupon\Series\GetListByUi($couponSeriesId);
                    $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
                    $curl->prepare($seriesListQuery);

                    $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
                    $seriesLimitListQuery->setTimeout(3 * $config->coreService->timeout);
                    $curl->prepare($seriesLimitListQuery);

                    $curl->execute();

                    $couponSeriesList = (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery);

                    if (isset($couponSeriesList[0])) {
                        $couponSeries = $couponSeriesList[0];
                    } else {
                        $couponSeries = new Model\Coupon\Series();
                        $couponSeries->id = $couponSeriesId;
                    }

                    $response->prize = [
                        'type'         => $playResult['result']['prizes']['type'],
                        'couponSeries' => $couponSeries,
                    ];
                }
            } catch (\EnterQuery\CoreQueryException $e) {
                switch ($e->getCode()) {
                    case 301:
                        throw new \Exception('Необходимо авторизоваться', Http\Response::STATUS_UNAUTHORIZED);
                        break;
                    case 311: case 312:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Выши попытки израсходованы. Приходите завтра.'];
                        break;
                    case 612:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Вам нужно быть участником программы Enter Prize'];
                        break;
                    default:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage()];
                        break;
                }
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
        /** @var string[] */
        public $errors = [];
    }
}