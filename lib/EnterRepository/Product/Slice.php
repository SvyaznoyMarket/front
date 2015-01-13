<?php

namespace EnterRepository\Product;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Slice {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getTokenByHttpRequest(Http\Request $request) {
        $token = $request->query['sliceToken'];

        return $token;
    }

    /**
     * @param Query $query
     * @return Model\Product\Slice
     */
    public function getObjectByQuery(Query $query) {
        $slice = null;

        if ($item = $query->getResult()) {
            $slice = new Model\Product\Slice($item);
        }

        return $slice;
    }
}