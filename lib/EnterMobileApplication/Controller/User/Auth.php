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
        use ErrorTrait;
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

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

            $username = is_scalar($request->data['username']) ? trim((string)$request->data['username']) : null;
            if (!$username) {
                throw new \Exception('Не передан username', Http\Response::STATUS_BAD_REQUEST);
            }

            $password = is_scalar($request->data['password']) ? trim((string)$request->data['password']) : null;
            if (!$password) {
                throw new \Exception('Не передан password', Http\Response::STATUS_BAD_REQUEST);
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

                $token = $tokenQuery->getResult()['token'];
                if (empty($token)) {
                    throw new \Exception('Не получен token пользователя');
                }

                $response->token = $token;
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                $response->errors = $this->getErrorsByException($e);
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