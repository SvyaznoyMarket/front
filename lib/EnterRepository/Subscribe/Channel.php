<?php

namespace EnterRepository\Subscribe;

use Enter\Http;
use Enter\Curl\Query;
use EnterModel as Model;

class Channel {
    /**
     * @param Query $query
     * @return Model\Subscribe\Channel[]
     */
    public function getObjectListByQuery(Query $query) {
        $subscribes = [];

        foreach ($query->getResult() as $item) {
            if (!isset($item['id'])) continue;

            $subscribes[] = new Model\Subscribe\Channel($item);
        }

        return $subscribes;
    }
}