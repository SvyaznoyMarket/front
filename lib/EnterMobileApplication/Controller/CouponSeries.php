<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\CouponList\Response;

    class CouponSeries {
        use ConfigTrait, LoggerTrait, CurlTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'token' => '',
                'couponSeries' => [],
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\CouponList {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
        /** @var Model\Coupon\Series[] */
        public $couponSeries = [];
    }
}