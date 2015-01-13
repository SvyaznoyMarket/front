<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByToken extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $token
     * @param string $regionId
     */
    public function __construct($token, $regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/category/get';
        $this->url->query = [
            'slug'   => [$token],
            'geo_id' => $regionId,
        ];

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