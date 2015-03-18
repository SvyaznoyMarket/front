<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\SearchQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetAvailableList extends Query {
    use SearchQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $rootCriteria
     * @param string|null $regionId
     * @param int|null $depth
     * @param array $filterData
     */
    public function __construct($rootCriteria, $regionId = null, $depth = null, array $filterData = []) {
        $this->url = new Url();
        $this->url->path = 'category/get-available';
        $this->url->query = [
            'is_load_parents' => true,
        ];
        if (!empty($rootCriteria['id'])) {
            $this->url->query['root_id'] = $rootCriteria['id'];
        } else if (!empty($rootCriteria['token'])) {
            $this->url->query['root_slug'] = $rootCriteria['token'];
        }

        if ($regionId) {
            $this->url->query['region_id'] = $regionId;
        }
        if (is_int($depth)) {
            $this->url->query['depth'] = $depth;
        }
        if ((bool)$filterData) {
            $this->url->query['filter'] = [
                'filters' => $filterData,
            ];
        }

        $this->url->query['filter']['filters'][] = ['exclude_partner_type', 1, 2]; // AG-59 Временная заглушка для отключения кухонь

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['id']) ? $data : null;
    }
}