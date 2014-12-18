<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\User\ConfirmPhone\Response;

    class ConfirmPhone {
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

            $phone = is_scalar($request->data['phone']) ? (string)$request->data['phone'] : null;
            if (!$phone) {
                throw new \Exception('Не указан phone', Http\Response::STATUS_BAD_REQUEST);
            }

            $code = is_scalar($request->data['code']) ? (string)$request->data['code'] : null;

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

            $confirmQuery = new Query\User\ConfirmPhone($token, $phone, $code);
            $confirmQuery->setTimeout(10 * $config->coreService->timeout);

            $curl->query($confirmQuery);

            try {
                $result = $confirmQuery->getResult();
                if (empty($result['code'])) {
                    throw new \Exception('Неизвестная ошибка');
                }
                $result += ['code' => null, 'message' => null, 'user_id' => null];

                $response->result = [
                    'code'    => $result['code'],
                    'message' => $result['message'],
                ];

                $detail = $result['detail'];
            } catch (\EnterQuery\CoreQueryException $e) {
                $detail = $e->getDetail();

                if (400 == $e->getCode()) {
                    $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'field' => 'code']; // Указан неверный код
                } else if (403 == $e->getCode()) {
                    $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'field' => 'code']; // Повторная отправка не разрешена
                } else if (409 == $e->getCode()) {
                    $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'field' => 'phone']; // Этот телефон уже подтвержден
                } else if (410 == $e->getCode()) {
                    $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'field' => 'code']; // Лимит попыток исчерпан | Код просрочен
                } else if (600 == $e->getCode()) {
                    $response->errors[] = ['code' => $e->getCode(), 'message' => 'Некорректный номер телефона', 'field' => 'phone']; // Некорректно введен телефон
                } else {
                    throw $e;
                }
            }

            $response->remainingTime = isset($detail['expired']) ? $detail['expired'] : null;
            $response->attemptCount = isset($detail['attempts']) ? $detail['attempts'] : null;
            $response->remainingTime = isset($detail['retry']) ? $detail['retry'] : null;

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\ConfirmPhone {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
        /** @var array|null */
        public $result;
        /**
         * Время жизни кода в секундах
         * @var int
         */
        public $remainingTime;
        /**
         * Количество оставшихся попыток
         * @var
         */
        public $attemptCount;
        /**
         * Количество секунд до разрешения на повторную отправку
         * @var
         */
        public $retryTime;
         /** @var array[] */
        public $errors = [];
    }
}