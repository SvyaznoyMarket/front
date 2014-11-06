<?php

namespace EnterQuery\Product\Catalog\Config;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByProductCategoryLink extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $categoryLink
     * @param $regionId
     */
    public function __construct($categoryLink, $regionId) {
        $this->url = new Url();
        $this->url->path = 'category/get/v1';
        $this->url->query = [
            'url'    => $categoryLink,
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