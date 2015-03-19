<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\SearchQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

/**
 * @deprecated
 */
class GetTreeList extends Query {
    use SearchQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string|null $regionId
     * @param int|null $maxLevel
     * @param array $filterData
     * @param string|null $rootId
     */
    public function __construct($regionId = null, $maxLevel = null, array $filterData = [], $rootId = null) {
        $this->url = new Url();
        $this->url->path = 'category/tree';
        $this->url->query = [
            'is_load_parents' => true,
        ];
        $this->url->query['max_level'] = $maxLevel ?: 6;
        if ($regionId) {
            $this->url->query['region_id'] = $regionId;
        }
        if ((bool)$filterData) {
            $this->url->query['filter'] = [
                'filters' => $filterData,
            ];
        }
        if ($rootId) {
            $this->url->query['root_id'] = $rootId;
            $this->url->query['is_load_parents'] = false;
        }

        // AG-59 Кухни по слотам
        $this->url->query['filter']['filters'][] = ['exclude_partner_type', 1, 2];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]) ? $data : [];
    }
}