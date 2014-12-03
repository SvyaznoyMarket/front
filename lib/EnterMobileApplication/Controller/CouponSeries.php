<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\CouponList\Response;

    class CouponSeries {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            //$session = $this->getSession();

            // ответ
            $response = new Response();

            $seriesListQuery = new Query\Coupon\Series\GetList(null);
            $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($seriesListQuery);

            $curl->execute();

            $response->couponSeries = (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery);

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\CouponList {
    use EnterModel as Model;

    class Response {
        /** @var Model\Coupon\Series[] */
        public $couponSeries = [];
    }
}