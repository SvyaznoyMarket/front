<?php

namespace EnterQuery\Point;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListFromScms extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $regionId
     */
    public function __construct($regionId) {
        $this->url = new Url();
        $this->url->path = 'api/point/get';

        $this->url->query = [
            'geo_id' => $regionId,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $this->result = $this->parse($response);
    }
}