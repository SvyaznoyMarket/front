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
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $couponRepository = new \EnterRepository\Coupon();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос пользователя
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            $curl->execute();

            // получение пользователя
            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            if ($user) {
                $response->token = $token;
            }

            // список купонов
            $couponListQuery = new Query\Coupon\GetListByUserToken($token);
            $couponListQuery->setTimeout(10 * $config->coreService->timeout);
            $curl->prepare($couponListQuery);

            // список лимитов серий купонов
            $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
            $seriesLimitListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($seriesLimitListQuery);

            // список серий купонов
            $seriesListQuery = new Query\Coupon\Series\GetList(/*$user->isEnterprizeMember ? '1' : null*/null);
            $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($seriesListQuery);

            $curl->execute();

            $filteredCouponsAndCouponSeries = $couponRepository->getFilteredCouponsAndCouponSeriesByQuery($couponListQuery, $seriesLimitListQuery, $seriesListQuery);
            $response->coupons = $filteredCouponsAndCouponSeries['coupons'];
            $response->couponSeries = $filteredCouponsAndCouponSeries['couponSeries'];

            // срезы для серий купонов
            /*
            $sliceTokensBySeriesId = [];
            foreach ($response->couponSeries as $couponSeries) {
                if ($couponSeries->productSegment->url && preg_match('/\/slices\/([\w\d-_]+)/', $couponSeries->productSegment->url, $matches)) {
                    if (empty($matches[1])) continue;
                    $sliceTokensBySeriesId[$couponSeries->id] = $matches[1];
                }
            }
            */

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
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