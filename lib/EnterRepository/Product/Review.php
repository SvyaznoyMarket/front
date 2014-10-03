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
            foreach ($query->getResult()['review_list'] as $item) {
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
            $count = $query->getResult()['num_reviews'];
        } catch (\Exception $e) {
            //trigger_error($e, E_USER_ERROR);
        }

        return $count;
    }
}