<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class Cancel extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $orderId
     * @param string $userToken
     */
    public function __construct($orderId, $userToken) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/order/cancel-request';
        $this->url->query = [
            'id'    => $orderId,
            'token' => $userToken,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        if (is_array($data)) {
            $this->result = $data;
        }
    }
}