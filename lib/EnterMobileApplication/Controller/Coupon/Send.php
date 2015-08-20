<?php

namespace EnterMobileApplication\Controller\Coupon {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Coupon\Send\Response;

    class Send {
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

namespace EnterMobileApplication\Controller\Coupon\Send {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $transactionId;
        /** @var string|null */
        public $token;
         /** @var array[] */
        public $errors = [];
    }
}