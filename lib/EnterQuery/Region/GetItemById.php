<?php

namespace EnterQuery\Region;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItemById extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $id
     */
    public function __construct($id) {
        $this->url = new Url();
        $this->url->path = 'api/geo/get-town';
        $this->url->query = [
            'id' => [$id],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result'][0]['id']) ? $data['result'][0] : null;
    }
}