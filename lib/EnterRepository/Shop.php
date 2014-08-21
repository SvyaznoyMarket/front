<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterModel as Model;

class Shop {
    /**
     * @param Query $query
     * @return Model\Shop
     */
    public function getObjectByQuery(Query $query) {
        $shop = null;

        $item = $query->getResult();
        if ($item) {
            $shop = new Model\Shop($item);
        }

        return $shop;
    }

    /**
     * @param Query $query
     * @return Model\Shop[]
     */
    public function getIndexedObjectListByQuery(Query $query) {
        $shops = [];

        foreach ($query->getResult() as $item) {
            if (empty($item['id'])) continue;

            $shops[(string)$item['id']] = new Model\Shop($item);
        }

        return $shops;
    }
}