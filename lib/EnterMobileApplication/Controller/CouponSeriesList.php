<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\CouponSeriesList\Response;

    class CouponSeriesList {
        use ConfigTrait, LoggerTrait, CurlTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $couponSeriesRepository = new \EnterRepository\Coupon\Series();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            // запрос пользователя
            $userItemQuery = null;
            if ($token) {
                $userItemQuery = (0 !== strpos($token, 'anonymous-')) ? new Query\User\GetItemByToken($token) : null;

                if ($userItemQuery) {
                    $curl->prepare($userItemQuery)->execute();
                }
            }

            // получение пользователя
            $user = $userItemQuery ? (new \EnterRepository\User())->getObjectByQuery($userItemQuery) : null;
            if ($user) {
                $response->token = $token;
            }

            // список купонов
            $couponListQuery = null;
            if ($user && $token) {
                $couponListQuery = new Query\Coupon\GetListByUserToken($token);
                $couponListQuery->setTimeout(5 * $config->coreService->timeout);
                $curl->prepare($couponListQuery);
            }

            // список лимитов серий купонов
            $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
            $seriesLimitListQuery->setTimeout(5 * $config->coreService->timeout);
            $curl->prepare($seriesLimitListQuery);

            // список серий купонов
            $seriesListQuery = new Query\Coupon\Series\GetList(null);
            $seriesListQuery->setTimeout(5 * $config->coreService->timeout);
            $curl->prepare($seriesListQuery);

            $curl->execute();

            $usedSeriesIds = [];

            if ($couponListQuery) {
                $coupons = (new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery);
                foreach ($coupons as $coupon) {
                    $usedSeriesIds[] = $coupon->seriesId;
                }
            }

            $response->couponSeries = $couponSeriesRepository->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery);
            $response->couponSeries = $couponSeriesRepository->filterObjectList($response->couponSeries, $usedSeriesIds, $user);

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\CouponSeriesList {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
        /** @var Model\Coupon\Series[] */
        public $couponSeries = [];
    }
}