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
    use EnterMobileApplication\Controller\User\SocialAuth\Response;

    class SocialAuth {
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
            $session = $this->getSession();

            // ответ
            $response = new Response();

            $email = is_scalar($request->data['email']) ? trim((string)$request->data['email']) : null;
            if (!$email) {
                throw new \Exception('Не передан email', Http\Response::STATUS_BAD_REQUEST);
            }

            $type = is_scalar($request->data['type']) ? trim((string)$request->data['type']) : null;
            if (!$type) {
                throw new \Exception('Не передан type', Http\Response::STATUS_BAD_REQUEST);
            }

            $accessToken = is_scalar($request->data['accessToken']) ? trim((string)$request->data['accessToken']) : null;
            if (!$accessToken) {
                throw new \Exception('Не передан accessToken', Http\Response::STATUS_BAD_REQUEST);
            }

            $userId = is_scalar($request->data['userId']) ? trim((string)$request->data['userId']) : null;
            if (!$userId) {
                throw new \Exception('Не передан userId', Http\Response::STATUS_BAD_REQUEST);
            }

            try {
                $tokenQuery = new Query\User\GetTokenBySocialToken($type, $userId, $accessToken, $email);
                $tokenQuery->setTimeout($config->coreService->timeout * 3);
                $curl->query($tokenQuery);

                $token = $tokenQuery->getResult();
                if (empty($token)) {
                    throw new \Exception('Не получен token пользователя');
                }

                $session->set($config->userToken->authName, $token);
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

namespace EnterMobileApplication\Controller\User\SocialAuth {
    class Response {
        /** @var string */
        public $token;
        /** @var string[] */
        public $errors = [];
    }
}