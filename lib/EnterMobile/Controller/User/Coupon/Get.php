<?php

namespace EnterMobile\Controller\User\Coupon;

use Enter\Http;
use EnterMobile\Controller\SecurityTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\RouterTrait;
use EnterQuery as Query;
use EnterModel as Model;

class Get {
    use SecurityTrait, ConfigTrait, MustacheRendererTrait, LoggerTrait, CurlTrait, RouterTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $responseData = [];

        $userToken = $this->getUserToken($request);

        // запрос пользователя
        $userItemQuery = new Query\User\GetItemByToken($userToken);
        $curl->prepare($userItemQuery);

        $curl->execute();

        // получение пользователя
        $user = $this->getUser($userItemQuery);

        // список купонов
        $couponListQuery = new Query\Coupon\GetListByUserToken($userToken);
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

        $usedSeriesIds = [];
        $coupons = (new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery);
        foreach ($coupons as $coupon) {
            if ($coupon->isUsed) continue;
            $usedSeriesIds[] = $coupon->seriesId;
        }

        /** @var Model\Coupon\Series[] $couponSeries */
        $couponSeries = array_values(
            array_filter( // фильрация серий купонов
                (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery),
                function(Model\Coupon\Series $series) use (&$usedSeriesIds) {
                    return in_array($series->id, $usedSeriesIds); // только те серии купонов, которые есть у ранее полученых купонов
                }
            )
        );

        // template data
        $page = [
            'coupons' => call_user_func(function() use (&$couponSeries) {
                $data = [];

                foreach ($couponSeries as $iCouponSeries) {
                    $data[] = [
                        'image' => $iCouponSeries->backgroundImageUrl,
                        //'name'  => $iCouponSeries->,
                    ];
                }

                return $data;
            }),
        ];

        // рендер
        $renderer = $this->getRenderer();
        $content = $renderer->render('page/order/delivery/user-discount-list', $page);

        // http-ответ
        return new Http\JsonResponse([
            'content' => $content
        ]);
    }
}