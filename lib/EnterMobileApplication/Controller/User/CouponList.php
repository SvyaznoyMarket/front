<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\User\CouponList\Response;

    class CouponList {
        use ConfigTrait, LoggerTrait, CurlTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            return new Http\JsonResponse([
                'token' => '',
                'coupons' => [],
                'couponSeries' => [],
            ]);
        }
    }
}

namespace EnterMobileApplication\Controller\User\CouponList {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var Model\Coupon[] */
        public $coupons = [];
        /** @var Model\Coupon\Series[] */
        public $couponSeries = [];
    }
}