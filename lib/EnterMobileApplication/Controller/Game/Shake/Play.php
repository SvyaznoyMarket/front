<?php

namespace EnterMobileApplication\Controller\Game\Shake {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Game\Shake\Play\Response;

    class Play {
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

namespace EnterMobileApplication\Controller\Game\Shake\Play {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $transactionId;
        /** @var string|null */
        public $token;
        /** @var string */
        public $state;
        /** @var array|null */
        public $prize;
        /** @var string[] */
        public $errors = [];
    }
}