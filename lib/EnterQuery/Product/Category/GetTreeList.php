<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetTreeList extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string|null $regionId
     * @param int|null $maxLevel
     * @param array $filterData
     */
    public function __construct($regionId = null, $maxLevel = null, array $filterData = []) {
        $this->url = new Url();
        $this->url->path = 'v2/category/tree';
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