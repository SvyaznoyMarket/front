<?php

namespace EnterQuery\Shop;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByIdRegionId extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $regionId
     */
    public function __construct($regionId) {
        $this->url = new Url();
        $this->url->path = 'shop/get';
        $this->url->query = [
            'geo_id' => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['id']) ? $data : [];
    }
}