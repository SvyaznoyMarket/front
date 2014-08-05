<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetBranchItemByCategoryObject extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param Model\Product\Category $category
     * @param string $regionId
     * @param array $filterData
     */
    public function __construct(Model\Product\Category $category, $regionId = null, array $filterData = []) {
        $this->url = new Url();
        $this->url->path = 'v2/category/tree';
        $this->url->query = [
            'root_id'         => $category->id,
            'max_level'       => $category->level + 1,
            'is_load_parents' => true,
        ];
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

        $this->result = isset($data[0]['id']) ? $data[0] : null;
    }
}