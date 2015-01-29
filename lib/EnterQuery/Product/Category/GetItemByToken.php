<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByToken extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $token
     * @param string $regionId
     */
    public function __construct($token, $regionId) {
        $this->url = new Url();
        $this->url->path = 'category/get/v1';
        $this->url->query = [
            'slug'   => $token,
            'geo_id' => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['id']) ? $data : null;
    }
}