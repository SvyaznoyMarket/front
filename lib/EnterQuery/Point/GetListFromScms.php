<?php

namespace EnterQuery\Point;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListFromScms extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string|null $regionId
     * @param string[] $uis
     * @param array[] $filter
     */
    public function __construct($regionId = null, $uis = [], $filter = []) {
        $this->url = new Url();
        $this->url->path = 'api/point/get';

        if ($regionId) {
            $this->url->query['geo_id'] = $regionId;
        }

        if ($uis) {
            $this->url->query['uids'] = $uis;
        }

        if (isset($filter) && isset($filter['partners'])) {
            $this->url->query['partner_slugs'] = $filter['partners'];
        }

        if (isset($filter) && isset($filter['coordinates'])) {
            $this->url->query['polygon'] = $filter['coordinates'];
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);

        // TODO: удалить после реализации FCMS-782
        // Исключаем партнёров, для которых нет точек
        $availablePartners = array_unique(array_map(function($point){ return $point['partner']; }, $this->result['points']));
        $this->result['partners'] = array_filter($this->result['partners'], function($partner) use(&$availablePartners) {
            return in_array($partner['slug'], $availablePartners, true);
        });

        // TODO: удалить после реализации FCMS-781
        // Сортируем партнёров
        $partnerOrder = ['enter', 'euroset', 'pickpoint', 'hermes', 'svyaznoy'];
        usort($this->result['partners'], function ($a, $b) use(&$partnerOrder){ return array_search($a['slug'], $partnerOrder) > array_search($b['slug'], $partnerOrder);});
    }
}