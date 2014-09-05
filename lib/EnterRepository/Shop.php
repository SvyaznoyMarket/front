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
     * @param callable|null $parser
     * @return Model\Shop[]
     */
    public function getIndexedObjectListByQuery(Query $query, $parser = null) {
        $shops = [];

        /** @var callable|null $parser */
        $parser = is_callable($parser) ? $parser : null;

        foreach ($query->getResult() as $item) {
            if (empty($item['id'])) continue;

            if ($parser) $parser($item);

            $shops[(string)$item['id']] = new Model\Shop($item);
        }

        return $shops;
    }
}