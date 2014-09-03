<?php

namespace EnterQuery\Product\Catalog\Config;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByProductCategoryUi extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param Model\Product\Category[] $categories
     * @param Model\Product|null $product
     */
    public function __construct($categoryUi, $regionId) {
        $this->url = new Url();
        $this->url->path = 'category/get';
        $this->url->query = [
            'uid' => $categoryUi,
            'geo_id' => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}