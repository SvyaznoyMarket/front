<?php

namespace EnterMobileApplication\Controller\Coupon\Enterprize {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Coupon\Enterprize\Create\Response;

    class Create {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
            }

            $couponSeriesId = is_scalar($request->query['couponSeriesId']) ? (string)$request->query['couponSeriesId'] : null;
            if (!$token) {
                throw new \Exception('Не указан couponSeriesId', Http\Response::STATUS_BAD_REQUEST);
            }

            $couponSeries = new Model\Coupon\Series();
            $couponSeries->id = $couponSeriesId;

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

            // получение данных из хранилища
            $storageGetQuery = new Query\Storage\GetItemByKey('user_id', $user->id);
            $curl->prepare($storageGetQuery)->execute();
            $storageData = (array)$storageGetQuery->getResult() + ['email' => null, 'mobile' => null];

            if (!$user->isEnterprizeMember) {
                $user->email = $storageData['email'];
            }
            if (!$user->email) {
                throw new \Exception('Нужно подтвердить email');
            }
            $user->phone = $storageData['mobile'];
            if (!$user->phone) {
                throw new \Exception('Нужно подтвердить телефон');
            }

            // создание купона
            $createQuery = new Query\Coupon\Enterprize\Create($token, $user, $couponSeries);
            $createQuery->setTimeout(10 * $config->coreService->timeout);

            $curl->query($createQuery);

            try {
                $result = $createQuery->getResult();
                $this->getLogger()->push(['core.result' => $result, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['enterprize']]);

                // удаление данных из хранилища
                $storageDeleteQuery = new Query\Storage\GetItemByKey('user_id', $user->id);
                $curl->prepare($storageDeleteQuery)->execute();
            } catch (\EnterQuery\CoreQueryException $e) {
                $detail = $e->getDetail();

                if (403 == $e->getCode()) {
                    if (isset($detail['mobile_confirmed']) && !$detail['mobile_confirmed']) {
                        $response->errors[] = ['code' => 400100, 'message' => 'Телефон не подтвержден', 'field' => 'phone'];
                    } elseif (isset($detail['email_confirmed']) && !$detail['email_confirmed']) {
                        $response->errors[] = ['code' => 400100, 'message' => 'Email не подтвержден', 'field' => 'email'];
                    } else {
                        $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'field' => null];
                    }
                } else if (600 == $e->getCode()) {
                    foreach ($detail as $fieldName => $errors) {
                        foreach ((array)$errors as $errorType => $errorMessage) {
                            switch ($fieldName) {
                                case 'name':
                                    $response->errors[] = ['code' => $e->getCode(), 'message' => ('isEmpty' === $errorType) ? 'Не заполнено имя' : 'Некорректно введено имя', 'field' => 'name'];
                                    break;
                                case 'mobile':
                                    $response->errors[] = ['code' => $e->getCode(), 'message' => ('isEmpty' === $errorType) ? 'Не заполнен номер телефона' : 'Некорректно введен номер телефона', 'field' => 'phone'];
                                    break;
                                case 'email':
                                    $response->errors[] = ['code' => $e->getCode(), 'message' => ('isEmpty' === $errorType) ? 'Не заполнен email' : 'Некорректно введен email', 'field' => 'email'];
                                    break;
                                case 'guid':
                                    $response->errors[] = ['code' => $e->getCode(), 'message' => ('isEmpty' === $errorType) ? 'Не передан идентификатор серии купона' : 'Невалидный идентификатор серии купона', 'field' => 'couponSeriesId'];
                                    break;
                                case 'agree':
                                    $response->errors[] = ['code' => $e->getCode(), 'message' => 'Необходимо согласие', 'field' => 'agree'];
                                    break;
                                default:
                                    $response->errors[] = ['code' => $e->getCode(), 'message' => 'Неизвестная ошибка', 'field' => null];
                            }
                        }
                    }
                } else {
                    throw $e;
                }
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Coupon\Enterprize\Create {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
         /** @var array[] */
        public $errors = [];
    }
}