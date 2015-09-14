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
}