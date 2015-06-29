<?php

namespace EnterQuery\Point;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $uis
     */
    public function __construct(array $uis = []) {
        $this->url = new Url();
        $this->url->path = 'v2/point/get';

        $this->url->query = [
            'uids' => $uis,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['ui']) ? $data : [];
    }
}