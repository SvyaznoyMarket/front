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
    use EnterMobileApplication\Controller\User\ResetPassword\Response;

    class ResetPassword {
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

            $isEmailAuth = strpos($username, '@');
            try {
                $resetQuery =
                    $isEmailAuth
                        ? new Query\User\ResetPasswordByEmail($username)
                        : new Query\User\ResetPasswordByPhone($username)
                ;
                $resetQuery->setTimeout(2 * $config->coreService->timeout);
                $curl->query($resetQuery);

                $result = $resetQuery->getResult();

                $response->success = isset($result['confirmed']) && $result['confirmed'];
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                $response->success = false;
                $response->errors = $this->getErrorsByException($e);
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterMobileApplication\Controller\User\ResetPassword {
    class Response {
        /** @var bool */
        public $success;
        /** @var string[] */
        public $errors = [];
    }
}