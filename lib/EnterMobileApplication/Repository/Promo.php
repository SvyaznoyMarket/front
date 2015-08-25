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

        foreach ($promos as $promoKey => $promo) {
            if (!$promo->target instanceof \EnterModel\Promo\Target\Content) {
                unset($promo->target->url);
            } else {
                $promo->target->url = 'http://m.enter.ru/' . $promo->target->contentId;
            }

            if ($promo->target instanceof \EnterModel\Promo\Target\Slice) {
                unset($promo->target->categoryToken);
            }

            // MAPI-102 Не возвращать баннеры с тегом отличным от mobile
            call_user_func(function() use(&$promos, $promoKey, $promo) {
                foreach ($promo->media->photos as $mediaKey => $media) {
                    if (!in_array('mobile', $media->tags, true)) {
                        unset($promo->media->photos[$mediaKey]);
                    }
                }

                $promo->media->photos = array_values($promo->media->photos);

                if (!$promo->media->photos) {
                    unset($promos[$promoKey]);
                }

                $promos = array_values($promos);
            });
        }
        
        return $promos;
    }
}