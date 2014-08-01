<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class CreatePacket extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($data) {
        $this->url = new Url();
        $this->url->path = 'v2/order/create-packet2';
        $this->data = $data;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = (bool)$data ? $data : null;
    }
}