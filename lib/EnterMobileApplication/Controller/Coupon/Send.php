<?php

namespace EnterMobileApplication\Controller\Coupon {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Coupon\Send\Response;

    class Send {
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

            $response->transactionId = is_scalar($request->query['transactionId']) ? (string)$request->query['transactionId'] : null;
            if (!$response->transactionId) {
                throw new \Exception('Не указан transactionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $couponSeriesId = is_scalar($request->query['couponSeriesId']) ? (string)$request->query['couponSeriesId'] : null;
            if (!$couponSeriesId) {
                throw new \Exception('Не указан couponSeriesId', Http\Response::STATUS_BAD_REQUEST);
            }
            $phone = $request->data['phone'];
            $email = $request->data['email'];
            $promoToken = is_scalar($request->data['promoToken']) ? (string)$request->data['promoToken'] : null;
            if (!$promoToken) {
                throw new \Exception('Не указан promoToken', Http\Response::STATUS_BAD_REQUEST);
            }

            $couponSeries = new Model\Coupon\Series();
            $couponSeries->id = $couponSeriesId;

            // запрос пользователя
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            $curl->execute();

            // получение пользователя
            $user = null;
            try {
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            } catch (\Exception $e) {}

            $response->token = $token;
            if (!$user) {
                $user = new Model\User();
                $user->email = $email;
                $user->phone = $phone;
            }

            if (!$user->email && !$user->phone) {
                throw new \Exception('Не указаны телефон или email', Http\Response::STATUS_BAD_REQUEST);
            }

            $sendQuery = new Query\Coupon\Send($response->transactionId, $couponSeries, $user, $promoToken);
            $sendQuery->setTimeout(10 * $config->coreService->timeout);

            $curl->query($sendQuery);

            try {
                $result = $sendQuery->getResult();
            } catch (\EnterQuery\CoreQueryException $e) {
                $detail = $e->getDetail();

                if (600 == $e->getCode()) {
                    foreach ($detail as $fieldName => $errors) {
                        $errors = is_array($errors) ? [key($errors) => reset($errors)] : [];
                        foreach ($errors as $errorType => $errorMessage) {
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
                                default:
                                    $response->errors[] = ['code' => $e->getCode(), 'message' => 'Неизвестная ошибка', 'field' => null];
                            }
                        }
                    }
                } else {
                    throw $e;
                }
            }

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Coupon\Send {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $transactionId;
        /** @var string|null */
        public $token;
         /** @var array[] */
        public $errors = [];
    }
}