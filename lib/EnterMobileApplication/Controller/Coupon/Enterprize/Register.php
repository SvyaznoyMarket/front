<?php

namespace EnterMobileApplication\Controller\Coupon\Enterprize {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterMobileApplication\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Coupon\Enterprize\Register\Response;

    class Register {
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
            if (!$couponSeriesId) {
                throw new \Exception('Не указан couponSeriesId');
            }

            $couponSeries = new Model\Coupon\Series();
            $couponSeries->id = $couponSeriesId;

            $phone = $request->data['phone'];
            if (!$phone) {
                throw new \Exception('Не указан phone');
            }
            $email = $request->data['email'];
            if (!$email) {
                throw new \Exception('Не указан email');
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
            //$response->token = $token;

            $enterprizeUser = clone $user;
            $enterprizeUser->phone = $phone;
            $enterprizeUser->email = $email;

            $registerQuery = new Query\Coupon\Enterprize\Register($token, $enterprizeUser, $couponSeries);
            $registerQuery->setTimeout(10 * $config->coreService->timeout);

            $curl->query($registerQuery);

            try {
                $registerResult = $registerQuery->getResult();

                $response->isPhoneConfirmed = (bool)@$registerResult['mobile_confirmed'];
                $response->isEmailConfirmed = (bool)@$registerResult['email_confirmed'];
                $response->token = (string)@$registerResult['token'];
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
                } else if (409 == $e->getCode()) {
                    $error = ['code' => $e->getCode(), 'message' => 'Уже зарегистрирован в ENTER PRIZE', 'field' => null];
                    if (isset($detail['mobile_in_enter_prize']) && $detail['mobile_in_enter_prize']) {
                        $error['field'] = 'phone';
                    } elseif (isset($detail['email_in_enter_prize']) && $detail['email_in_enter_prize']) {
                        $error['field'] = 'email';
                    }
                    $response->errors[] = $error;
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

            if ($response->isPhoneConfirmed && $response->isEmailConfirmed) { // если телефон и email подтверждены
                //return (new Controller\Coupon\Enterprize\Create())->execute($request);
            } else if ($response->isPhoneConfirmed) { // если подтвержден только телефон
                //return (new Controller\User\ConfirmEmail())->execute($request);
            } else if ($response->isEmailConfirmed) {
                //return (new Controller\User\ConfirmPhone())->execute($request);
            }

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Coupon\Enterprize\Register {
    use EnterModel as Model;

    class Response {
        /** @var bool */
        public $isPhoneConfirmed;
        /** @var bool */
        public $isEmailConfirmed;
        /** @var string|null */
        public $token;
         /** @var array[] */
        public $errors = [];
    }
}