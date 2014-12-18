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
    use EnterMobileApplication\Controller\User\UpdatePassword\Response;

    class UpdatePassword {
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

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
            }
            $password = is_scalar($request->data['password']) ? trim((string)$request->data['password']) : null;
            if (!$password) {
                throw new \Exception('Не передан password', Http\Response::STATUS_BAD_REQUEST);
            }
            $newPassword = is_scalar($request->data['newPassword']) ? trim((string)$request->data['newPassword']) : null;
            if (!$newPassword) {
                throw new \Exception('Не передан newPassword', Http\Response::STATUS_BAD_REQUEST);
            }

            try {
                $updateQuery = new Query\User\UpdatePassword($token, $password, $newPassword);
                $updateQuery->setTimeout(2 * $config->coreService->timeout);
                $curl->query($updateQuery);

                $result = $updateQuery->getResult();

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

namespace EnterMobileApplication\Controller\User\UpdatePassword {
    class Response {
        /** @var bool */
        public $success;
        /** @var string[] */
        public $errors = [];
    }
}