<?php

namespace EnterRepository\Product\Catalog;

use Enter\Curl\Query;
use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Config {
    use ConfigTrait, LoggerTrait;

    public function getLimitByHttpRequest(Http\Request $request) {
        $limit = (int)$request->query['limit'];
        if (($limit >= 400) || ($limit <= 0)) {
            $limit = $this->getConfig()->product->itemPerPage;
        }

        return $limit;
    }
}