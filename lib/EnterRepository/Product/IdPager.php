<?php

namespace EnterRepository\Product;

use Enter\Curl\Query;
use EnterSite\ConfigTrait;
use EnterModel as Model;

class IdPager {
    use ConfigTrait;

    public function getObjectByQuery(Query $query) {
        $pager = null;

        $item = $query->getResult();
        if ($item) {
            $pager = new Model\Product\IdPager($item);
        }

        return $pager;
    }
}