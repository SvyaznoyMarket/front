<?php

namespace EnterSite\Repository;

use Enter\Http;
use Enter\Curl\Query;
use EnterSite\Model;

class Shop {
    /**
     * @param Query $query
     * @return \EnterModel\Shop
     */
    public function getObjectByQuery(Query $query) {
        $shop = null;

        $item = $query->getResult();
        if ($item) {
            $shop = new \EnterModel\Shop($item);
        }

        return $shop;
    }
}