<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterModel as Model;

class Subscribe {
    /**
     * @param Query $query
     * @return Model\Subscribe[]
     */
    public function getObjectListByQuery(Query $query) {
        $subscribes = [];

        foreach ($query->getResult() as $item) {
            if (!isset($item['channel_id'])) continue;

            $subscribes[] = new Model\Subscribe($item);
        }

        return $subscribes;
    }
}