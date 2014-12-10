<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\User\ConfirmEmail\Response;

    class ConfirmEmail {
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

            $email = is_scalar($request->data['email']) ? (string)$request->data['email'] : null;
            if (!$email) {
                throw new \Exception('Не указан email');
            }

            $promoToken = is_scalar($request->data['promoToken']) ? (string)$request->data['promoToken'] : null;
            if (!$promoToken) {
                throw new \Exception('Не указан promoToken');
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

            $confirmQuery = new Query\User\ConfirmEmail($token, $email, $code, $promoToken);
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
                    $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'field' => 'email']; // Этот email уже подтвержден
                } else if (410 == $e->getCode()) {
                    $response->errors[] = ['code' => $e->getCode(), 'message' => $e->getMessage(), 'field' => 'code']; // Лимит попыток исчерпан | Код просрочен
                } else if (600 == $e->getCode()) {
                    $response->errors[] = ['code' => $e->getCode(), 'message' => 'Некорректный email', 'field' => 'email']; // Некорректно введен email
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

namespace EnterMobileApplication\Controller\User\ConfirmEmail {
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