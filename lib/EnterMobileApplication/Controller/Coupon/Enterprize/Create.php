<?php

namespace EnterMobileApplication\Controller\Coupon\Enterprize {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Coupon\Enterprize\Create\Response;

    class Create {
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

namespace EnterMobileApplication\Controller\Coupon\Enterprize\Create {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
         /** @var array[] */
        public $errors = [];
    }
}