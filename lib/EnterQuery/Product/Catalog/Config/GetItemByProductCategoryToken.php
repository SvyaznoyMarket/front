<?php

namespace EnterQuery\Product\Catalog\Config;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByProductCategoryToken extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $categoryToken
     * @param $regionId
     */
    public function __construct($categoryToken, $regionId) {
        $this->url = new Url();
        $this->url->path = 'category/get/v1';
        $this->url->query = [
            'slug'   => $categoryToken,
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