<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\Token\Response;

    class Token {
        use ConfigTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $session = $this->getSession();

            if (!$request->query['clientId']) {
                throw new \Exception('Не передан clientId');
            }

            $token = $session->get($config->userToken->authName);
            if (!$token) {
                // TODO: вынести в репозиторий
                $bytes = openssl_random_pseudo_bytes(32, $strong);
                if (true !== $strong) {
                    $bytes = false;
                }

                if (false === $bytes) {
                    $bytes = hash('sha256', uniqid(mt_rand(), true), true);
                }

                $token = 'anonymous-' . base_convert(bin2hex($bytes), 16, 36);

                $session->set($config->userToken->authName, $token);
            }

            // ответ
            $response = new Response();
            $response->token = $token;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Token {
    class Response {
        /** @var string */
        public $token;
    }
}