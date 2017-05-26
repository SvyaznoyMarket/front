<?php

namespace EnterQuery\Payment;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class CheckOrder extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($detail) {
        $this->url = new Url();
        $this->url->path = 'payment/robokassa-check-order';
        $this->url->query = $detail;
        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = is_array($data) ? $data : null;
    }
}