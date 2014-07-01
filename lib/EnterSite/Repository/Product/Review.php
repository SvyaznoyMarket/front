<?php

namespace EnterSite\Repository\Product;

use Enter\Curl\Query;
use EnterSite\Model;

class Review {
    /**
     * @param Query $query
     * @return \EnterModel\Product\Review[]
     */
    public function getObjectListByQuery(Query $query) {
        $reviews = [];

        try {
            foreach ($query->getResult() as $item) {
                $reviews[] = new \EnterModel\Product\Review($item);
            }
        } catch (\Exception $e) {
            //trigger_error($e, E_USER_ERROR);
        }

        return $reviews;
    }
}