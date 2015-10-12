<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Coupon {
    use LoggerTrait;

    /**
     * @param Query $query
     * @throws \Exception
     * @return Model\Coupon[]
     */
    public function getObjectListByQuery(Query $query) {
        $coupons = [];

        try {
            foreach ($query->getResult() as $item) {
                if (empty($item['number'])) continue;

                $coupons[] = new Model\Coupon($item);
            }
        } catch (\Exception $e) {
            if (402 == $e->getCode()) {
                throw new \Exception('Пользователь не авторизован', 401);
            }
            throw $e;
        }

        return $coupons;
    }

    /**
     * @param Model\Coupon[] $coupons
     * @return string[]
     */
    public function getSeriesIdListByObjectList($coupons) {
        $seriesIds = [];
        foreach ($coupons as $coupon) {
            $seriesIds[] = $coupon->seriesId;
        }

        return $seriesIds;
    }

    /**
     * @param Query $couponListQuery
     * @param Query $seriesLimitListQuery
     * @param Query $seriesListQuery
     * @return array
     * @throws \Exception
     */
    public function getFilteredCouponsAndCouponSeriesByQuery(Query $couponListQuery, Query $seriesLimitListQuery, Query $seriesListQuery) {
        $couponSeriesRepository = new \EnterRepository\Coupon\Series();

        $coupons = $this->getObjectListByQuery($couponListQuery);
        $couponSeries = $couponSeriesRepository->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery);

        $couponSeriesRepository->filterObjectListByIdList($couponSeries, $this->getSeriesIdListByObjectList($coupons));

        $couponSeriesIds = array_values(array_map(function(Model\Coupon\Series $couponSeries) {
            return $couponSeries->id;
        }, $couponSeries));

        $coupons = array_values(array_filter($coupons, function(Model\Coupon $coupon) use(&$couponSeriesIds) {
            return in_array($coupon->seriesId, $couponSeriesIds, true) && time() <= strtotime($coupon->endAt);
        }));

        // Фильтруем повторно уже с использованием отфильтрованных купонов
        $couponSeriesRepository->filterObjectListByIdList($couponSeries, $this->getSeriesIdListByObjectList($coupons));

        return ['coupons' => $coupons, 'couponSeries' => $couponSeries];
    }
}