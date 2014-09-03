<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\Auth\Response;

    class Auth {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $session = $this->getSession();

            // ответ
            $response = new Response();

            $username = is_scalar($request->data['username']) ? trim((string)$request->data['username']) : null;
            if (!$username) {
                throw new \Exception('Не передан username');
            }

            $password = is_scalar($request->data['password']) ? trim((string)$request->data['password']) : null;
            if (!$password) {
                throw new \Exception('Не передан password');
            }

            $isEmailAuth = strpos($username, '@');
            try {
                $tokenQuery =
                    $isEmailAuth
                        ? new Query\User\GetTokenByEmail($username, $password)
                        : new Query\User\GetTokenByPhone($username, $password)
                ;
                $tokenQuery->setTimeout($config->coreService->timeout * 2);
                $curl->query($tokenQuery);

                $token = $tokenQuery->getResult();
                if (empty($token)) {
                    throw new \Exception('Не получен token пользователя');
                }

                $session->set($config->userToken->authName, $token);
                $response->token = $token;
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                switch ($e->getCode()) {
                    case 613:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Неверный пароль', 'field' => 'password'];
                        break;
                    case 614:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Пользователь не найден', 'field' => 'username'];
                        break;
                    default:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Произошла ошибка. Возможно неверно указаны логин или пароль', 'field' => null];
                }
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Auth {
    class Response {
        /** @var string */
        public $token;
        /** @var string[] */
        public $errors = [];
    }
}