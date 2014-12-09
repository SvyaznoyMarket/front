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
                throw new \Exception('Не указан token');
            }

            $couponSeriesId = is_scalar($request->query['couponSeriesId']) ? (string)$request->query['couponSeriesId'] : null;
            if (!$token) {
                throw new \Exception('Не указан couponSeriesId');
            }

            $couponSeries = new Model\Coupon\Series();
            $couponSeries->id = $couponSeriesId;

            // запрос пользователя
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            // запрос купона


            $curl->execute();

            // получение пользователя
            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            if (!$user) {
                throw new \Exception('Пользователь не авторизован', Http\Response::STATUS_UNAUTHORIZED); // FIXME
            }
            $response->token = $token;

            $createCouponQuery = new Query\Coupon\Enterprize\Create($token, $user, $couponSeries);
            $createCouponQuery->setTimeout(3 * $config->coreService->timeout);

            $curl->prepare($createCouponQuery);

            $curl->execute();

            try {
                $result = $createCouponQuery->getResult();
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
                } elseif (600 == $e->getCode()) {
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
                }
            }

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