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
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            $couponSeriesId = is_scalar($request->query['id']) ? (string)$request->query['id'] : null;

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
            if ($token) {
                $couponListQuery = new Query\Coupon\GetListByUserToken($token);
                $couponListQuery->setTimeout(5 * $config->coreService->timeout);
                $curl->prepare($couponListQuery);
            }

            // список лимитов серий купонов
            $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
            $seriesLimitListQuery->setTimeout(5 * $config->coreService->timeout);
            $curl->prepare($seriesLimitListQuery);

            // список серий купонов
            if ($couponSeriesId) {
                $seriesListQuery = new Query\Coupon\Series\GetListByUi($couponSeriesId);
                $seriesListQuery->setTimeout(5 * $config->coreService->timeout);
                $curl->prepare($seriesListQuery);
            } else {
                $seriesListQuery = new Query\Coupon\Series\GetList(null);
                $seriesListQuery->setTimeout(5 * $config->coreService->timeout);
                $curl->prepare($seriesListQuery);
            }

            $curl->execute();

            $usedSeriesIds = []; // ид серий купонов
            if ($couponListQuery) {
                try {
                    foreach ($couponListQuery->getResult() as $couponItem) {
                        if (empty($couponItem['number'])) continue;

                        $coupon = new Model\Coupon($couponItem); // TODO: вынести в репозиторий
                        $usedSeriesIds[] = $coupon->seriesId;
                    }
                } catch (\Exception $e) {
                    if (402 == $e->getCode()) {
                        throw new \Exception('Пользователь не авторизован', 401);
                    }
                }
            }

            $response->couponSeries = array_values(
                array_filter(
                    (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery),
                    function(Model\Coupon\Series $series) use (&$usedSeriesIds, $user, &$couponSeriesId) {
                        return (
                            $couponSeriesId
                            || (
                                !in_array($series->id, $usedSeriesIds) // ... которые не были получены ранее
                                && $series->limit > 0 // ... у которых не исчерпан лимит
                                && ($series->isForNotMember || $series->isForNotMember) // ... которые хотя бы для участника ИЛИ неучастника // TODO: кажись, лишнее условие
                                && (
                                    (!$user || (!$user->isEnterprizeMember && $series->isForNotMember)) // ... которые для неучастников ИЛИ ...
                                    || ($user && $user->isEnterprizeMember && $series->isForMember) // ... которые для участников
                                )
                            )
                        );
                    }
                )
            );

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
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