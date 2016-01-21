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
     * @return array
     */
    public function getUpdateStub() {
        return [
            [
                "ui" => "",
                "name" => "Обнови приложение!",
                "target" => [
                    "type" => "Content",
                    "contentId" => "mobile_apps",
                ],
                "media" => [
                    "photos" => [
                        [
                            "uid" => "",
                            "contentType" => "image/jpeg",
                            "type" => "image",
                            "tags" => [
                                "mobile"
                            ],
                            "sources" => [
                                [
                                    "width" => "720",
                                    "height" => "240",
                                    "type" => "original",
                                    "url" => 'http://' . $this->getConfig()->hostname . ($this->getConfig()->version ? '/' . $this->getConfig()->version : '') . '/img/updateBanner.jpg',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

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

            $this->deleteNotMobileSourcesAndPromos($promos, $promoKey, $promo);
        }

        return $promos;
    }

    /**
     * MAPI-102 Не возвращать баннеры с тегом отличным от mobile
     */
    private function deleteNotMobileSourcesAndPromos(&$promos, $promoKey, $promo) {
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
    }
}