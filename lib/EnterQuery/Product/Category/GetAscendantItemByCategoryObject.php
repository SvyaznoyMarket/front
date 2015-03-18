<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\SearchQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetAscendantItemByCategoryObject extends Query {
    use SearchQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param Model\Product\Category $category
     * @param string $regionId
     */
    public function __construct(Model\Product\Category $category, $regionId = null) {
        $this->url = new Url();
        $this->url->path = 'category/tree';
        $this->url->query = [
            'root_id'         => $category->id,
            'max_level'       => $category->level - 1,
            'is_load_parents' => true,
        ];
        if ($regionId) {
            $this->url->query['region_id'] = $regionId;
        }

        $this->url->query['filter']['filters'][] = ['exclude_partner_type', 1, 2]; // AG-59 Временная заглушка для отключения кухонь

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