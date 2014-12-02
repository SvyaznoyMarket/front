<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\User\CouponList\Response;

    class CouponList {
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

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token');
            }
            $response->token = $token;

            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            $curl->execute();

            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);

            // список купонов
            $couponListQuery = new Query\Coupon\GetListByUserToken($token);
            $couponListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($couponListQuery);

            $seriesListQuery = new Query\Coupon\Series\GetList($user->isEnterprizeMember ? '1' : null);
            $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($seriesListQuery);

            $curl->execute();

            foreach ($couponListQuery->getResult() as $couponItem) {
                if (empty($couponItem['number'])) continue;

                $response->coupons[] = new Model\Coupon($couponItem); // TODO: вынести в репозиторий
            }

            $response->couponSeries = (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery);

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