<?php

namespace EnterQuery\Payment;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class ValidateHash extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($params) {
        $this->url = new Url();
        $this->url->path = 'payment/validate-hash';
        $this->url->query = $params;
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