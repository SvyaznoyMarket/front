<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\Get\Response;

    class Get {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

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

            try {
                $userItemQuery = new Query\User\GetItemByToken($token);
                $curl->prepare($userItemQuery);
                $curl->execute();

                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                if ($user) {
                    $favoriteListQuery = new Query\User\Favorite\GetListByUserUi($user->ui);
                    $curl->prepare($favoriteListQuery);

                    $orderListQuery = new Query\Order\GetListByUserToken($token, 0, 0);
                    $curl->prepare($orderListQuery);

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

                    $favoriteListResult = $favoriteListQuery->getResult() + ['products' => []];
                    $orderListResult = $orderListQuery->getResult() + ['total' => 0];
                    $filteredCouponsAndCouponSeries = $couponRepository->getFilteredCouponsAndCouponSeriesByQuery($couponListQuery, $seriesLimitListQuery, $seriesListQuery);

                    $response->token = $token;
                    $response->user = [
                        'id' => $user->id,
                        'ui' => $user->ui,
                        'firstName' => $user->firstName,
                        'lastName' => $user->lastName,
                        'middleName' => $user->middleName,
                        'sex' => $user->sex,
                        'phone' => $user->phone,
                        'homePhone' => $user->homePhone,
                        'email' => $user->email,
                        'occupation' => $user->occupation,
                        'birthday' => $user->birthday,
                        'svyaznoyClubCardNumber' => $user->svyaznoyClubCardNumber,
                        'isEnterprizeMember' => $user->isEnterprizeMember,
                        'regionId' => $user->regionId,
                        'region' => $user->region,
                        'orderCount' => $orderListResult['total'],
                        'favoriteCount' => count($favoriteListResult['products']),
                        'couponCount' => count($filteredCouponsAndCouponSeries['coupons']),
                    ];
                }

            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Get {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var array|null */
        public $user;
    }
}