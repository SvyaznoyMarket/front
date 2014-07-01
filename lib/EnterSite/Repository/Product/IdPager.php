<?php

namespace EnterSite\Repository\Product;

use Enter\Curl\Query;
use EnterSite\ConfigTrait;
use EnterSite\Model;

class IdPager {
    use ConfigTrait;

    public function getObjectByQuery(Query $query) {
        $pager = null;

        $item = $query->getResult();
        if ($item) {
            $pager = new \EnterModel\Product\IdPager($item);
        }

        return $pager;
    }
}