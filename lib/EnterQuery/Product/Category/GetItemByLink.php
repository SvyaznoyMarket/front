<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByLink extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $link
     * @param string $regionId
     */
    public function __construct($link, $regionId) {
        $this->url = new Url();
        $this->url->path = 'category/get/v1';
        $this->url->query = [
            'url'    => $link,
            'geo_id' => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['uid']) ? $data : null;
    }
}