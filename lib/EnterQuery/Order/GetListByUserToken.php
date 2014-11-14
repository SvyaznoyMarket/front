<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByUserToken extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $token
     */
    public function __construct($token) {
        $this->url = new Url();
        $this->url->path = 'v2/order/get-limited';
        $this->url->query = [
            'token' => $token,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['number']) ? $data : [];
    }
}