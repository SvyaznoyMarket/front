<?php

namespace EnterRepository\Product;

use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class UiPager {
    use ConfigTrait;

    public function getObjectByQuery(Query $query) {
        $pager = null;

        $item = $query->getResult();
        if ($item) {
            $pager = new Model\Product\UiPager($item);
        }

        return $pager;
    }
}