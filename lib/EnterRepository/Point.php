<?php

namespace EnterRepository;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Point {
    use ConfigTrait;


    /**
     * @param string $pointToken
     * @param null|string[] $tags
     * @return Model\MediaList
     */
    public function getMedia($pointToken, $tags = null) {
        switch ($pointToken) {
            case 'shops':
            case 'enter': // Приходит из метода http://scms.enter.ru/api/point/get
                $image = 'enter.png';
                break;
            case 'self_partner_pickpoint':
            case 'pickpoint': // Приходит из метода http://scms.enter.ru/api/point/get
                $image = 'pickpoint.png';
                break;
            case 'self_partner_svyaznoy':
            case 'shops_svyaznoy':
            case 'svyaznoy': // Приходит из метода http://scms.enter.ru/api/point/get
                $image = 'svyaznoy.png';
                break;
            case 'self_partner_euroset':
            case 'euroset': // Приходит из метода http://scms.enter.ru/api/point/get
                $image = 'euroset.png';
                break;
            case 'self_partner_hermes':
            case 'hermes': // Приходит из метода http://scms.enter.ru/api/point/get
                $image = 'hermes.png';
                break;
            default:
                $image = '';
                break;
        }

        $mediaList = new Model\MediaList();

        if ($image) {
            if (!$tags || in_array('logo', $tags, true)) {
                $mediaList->photos[] = new Model\Media([
                    'content_type' => 'image/png',
                    'provider' => 'image',
                    'tags' => ['logo'],
                    'sources' => [
                        [
                            'type' => '100x100',
                            'url' => 'http://' . $this->getConfig()->hostname . '/' . $this->getConfig()->version . '/img/points/logos/100x100/' . $image,
                            'width' => '100',
                            'height' => '100',
                        ],
                        [
                            'type' => '192x90',
                            'url' => 'http://' . $this->getConfig()->hostname . '/' . $this->getConfig()->version . '/img/points/logos/192x90/' . $image,
                            'width' => '192',
                            'height' => '90',
                        ],
                    ],
                ]);
            }

            if (!$tags || in_array('marker', $tags, true)) {
                $mediaList->photos[] = new Model\Media([
                    'content_type' => 'image/png',
                    'provider' => 'image',
                    'tags' => ['marker'],
                    'sources' => [
                        [
                            'type' => '31x40',
                            'url' => 'http://' . $this->getConfig()->hostname . '/' . $this->getConfig()->version . '/img/points/markers/31x40/' . $image,
                            'width' => '31',
                            'height' => '40',
                        ],
                        [
                            'type' => '61x80',
                            'url' => 'http://' . $this->getConfig()->hostname . '/' . $this->getConfig()->version . '/img/points/markers/61x80/' . $image,
                            'width' => '61',
                            'height' => '80',
                        ],
                    ],
                ]);
            }
        }

        return $mediaList;
    }

    /**
     * @param \EnterQuery\Point\GetListFromScms $query
     * @return \EnterModel\Point[]
     */
    public function getIndexedByUiObjectListByQuery(\EnterQuery\Point\GetListFromScms $query) {
        $points = [];
        $groupsById = [];
        $result = $query->getResult();

        if (isset($result['partners']) && is_array($result['partners'])) {
            foreach ($result['partners'] as $partner) {
                $group = new \EnterModel\Point\Group($partner);
                $groupsById[$group->id] = $group;
            }
        }

        if (isset($result['points']) && is_array($result['points'])) {
            foreach ($result['points'] as $pointItem) {
                $point = new \EnterModel\Point($pointItem);
                if (isset($groupsById[$point->group->id])) {
                    $point->group = $groupsById[$point->group->id];
                }

                $points[$point->ui] = $point;
            }
        }

        return $points;
    }
}