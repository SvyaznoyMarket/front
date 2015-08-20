<?php

namespace EnterMobileApplication\Controller\Coupon\Enterprize {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterMobileApplication\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Coupon\Enterprize\Register\Response;

    class Register {
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

namespace EnterMobileApplication\Controller\Coupon\Enterprize\Register {
    use EnterModel as Model;

    class Response {
        /** @var bool */
        public $isPhoneConfirmed;
        /** @var bool */
        public $isEmailConfirmed;
        /** @var string|null */
        public $token;
         /** @var array[] */
        public $errors = [];
    }
}