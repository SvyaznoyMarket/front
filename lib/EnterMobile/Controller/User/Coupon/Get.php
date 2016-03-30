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
        $session = $this->getSession();
        $couponSeriesRepository = new \EnterRepository\Coupon\Series();

        $userToken = $this->getUserToken($session, $request);

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
        $seriesListQuery = new Query\Coupon\Series\GetList($user->isEnterprizeMember ? '1' : null);
        $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
        $curl->prepare($seriesListQuery);

        $curl->execute();

        $usedSeriesIds = [];
        $coupons = (new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery);
        foreach ($coupons as $coupon) {
            if (!$coupon->isUsed) continue;
            $usedSeriesIds[] = $coupon->seriesId;
        }

        /** @var Model\Coupon\Series[] $couponSeries */
        $couponSeries = $couponSeriesRepository->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery);
        $couponSeries = $couponSeriesRepository->filterObjectList($couponSeries, $usedSeriesIds, $user);

        $couponSeriesById = [];
        foreach ($couponSeries as $iCouponSeries) {
            $couponSeriesById[$iCouponSeries->id] = $iCouponSeries;
        }


        // template data
        $page = [
            'coupons' => call_user_func(function() use (&$couponSeriesById, &$coupons) {
                $data = [];

                foreach ($coupons as $coupon) {
                    /** @var \EnterModel\Coupon\Series|null $iCouponSeries */
                    $iCouponSeries = isset($couponSeriesById[$coupon->seriesId]) ? $couponSeriesById[$coupon->seriesId] : null;
                    if (!$iCouponSeries) continue;

                    $data[] = [
                        'image'     => $iCouponSeries->backgroundImageUrl,
                        'name'      => $iCouponSeries->productSegment ? $iCouponSeries->productSegment->name : null,
                        'discount'  =>
                            $iCouponSeries->discount
                            ? [
                                'value'      => $iCouponSeries->discount->value,
                                'isCurrency' => '%' !== $iCouponSeries->discount->unit,
                            ]
                            : null
                        ,
                        'dataValue' => $coupon->number,
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