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
            $bytes = openssl_random_pseudo_bytes(32, $strong);
            if (true !== $strong) {
                $bytes = false;
            }

            if (false === $bytes) {
                $bytes = hash('sha256', uniqid(mt_rand(), true), true);
            }

            $token = 'anonymous-' . base_convert(bin2hex($bytes), 16, 36);

            return new Http\JsonResponse(['token' => $token]);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Token {
    class Response {
        /** @var string */
        public $token;
    }
}