<?php

namespace EnterQuery\Promo;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $regionId
     */
    public function __construct($regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/promo/get';
        $this->url->query = [
            'geo_id'    => $regionId,
            'client_id' => 'site', // FIXME, please FIME!!!
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