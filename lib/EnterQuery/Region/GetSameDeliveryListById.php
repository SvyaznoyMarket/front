<?php

namespace EnterQuery\Region;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetSameDeliveryListById extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $id
     */
    public function __construct($id) {
        $this->url = new Url();
        $this->url->path = 'v2/geo/get-in-same-delivery-zone';
        $this->url->query = [
            'geo_id' => $id,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]) ? $data : [];
    }
}