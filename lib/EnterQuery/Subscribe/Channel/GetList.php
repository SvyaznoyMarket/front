<?php

namespace EnterQuery\Subscribe\Channel;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result = [];

    public function __construct() {
        $this->url = new Url();
        $this->url->path = 'v2/subscribe/get-channel';

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