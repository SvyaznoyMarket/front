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
            return new Http\JsonResponse([]);
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