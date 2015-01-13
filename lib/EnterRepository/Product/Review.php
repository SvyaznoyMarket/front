<?php

namespace EnterRepository\Product;

use Enter\Curl\Query;
use EnterModel as Model;

class Review {
    /**
     * @param Query $query
     * @return Model\Product\Review[]
     */
    public function getObjectListByQuery(Query $query) {
        $reviews = [];

        try {
            $result = (array)$query->getResult() + ['review_list' => null];

            foreach ((array)$result['review_list'] as $item) {
                $reviews[] = new Model\Product\Review($item);
            }
        } catch (\Exception $e) {
            //trigger_error($e, E_USER_ERROR);
        }

        return $reviews;
    }

    /**
     * @param Query $query
     * @return int
     */
    public function countObjectListByQuery(Query $query) {
        $count = 0;

        try {
            $result = $query->getResult();
            $count = isset($result['num_reviews']) ? $result['num_reviews'] : null;
        } catch (\Exception $e) {
            //trigger_error($e, E_USER_ERROR);
        }

        return $count;
    }
}