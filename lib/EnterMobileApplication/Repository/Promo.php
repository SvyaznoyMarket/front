<?php

namespace EnterMobileApplication\Repository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Promo extends \EnterRepository\Promo {
    use LoggerTrait, ConfigTrait;

    /**
     * @param Query $query
     * @return Model\Promo[]
     */
    public function getObjectListByQuery(Query $query) {
        $promos = parent::getObjectListByQuery($query);

        foreach ($promos as $promo) {
            if (!$promo->target instanceof \EnterModel\Promo\Target\Content) {
                unset($promo->target->url);
            } else {
                $promo->target->url = 'http://m.enter.ru/' . $promo->target->contentId;
            }

            if ($promo->target instanceof \EnterModel\Promo\Target\Slice) {
                unset($promo->target->categoryToken);
            }
        }
        
        return $promos;
    }
}