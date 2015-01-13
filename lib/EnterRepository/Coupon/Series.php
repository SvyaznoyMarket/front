<?php

namespace EnterRepository\Coupon;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Series {
    use LoggerTrait;

    /**
     * @param Query $query
     * @param Query $limitQuery
     * @throws \Exception
     * @return Model\Coupon\Series[]
     */
    public function getObjectListByQuery(Query $query, Query $limitQuery = null) {
        $seriesList = [];

        $limitsByUi = [];
        if ($limitQuery) {
            foreach ($limitQuery->getResult() as $ui => $limit) {
                if (!is_string($ui)) {
                    throw new \Exception('Значение ui должно быть типа string');
                }
                if (!is_integer($limit) && (null !== $limit)) {
                    throw new \Exception('Значение limit должно быть типа integer');
                }

                $limitsByUi[$ui] = $limit;
            }
        }

        foreach ($query->getResult() as $item) {
            if (empty($item['uid'])) continue;

            $series = new Model\Coupon\Series($item);

            if (isset($limitsByUi[$series->id])) {
                $series->limit = $limitsByUi[$series->id];
            }

            $seriesList[] = $series;
        }

        return $seriesList;
    }
}