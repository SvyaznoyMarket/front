<?php

namespace EnterQuery\Payment;

use Enter\Curl\Query;
use EnterQuery\CorePrivateQueryTrait;
use EnterQuery\Url;

class GetConfig extends Query {
    use CorePrivateQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($methodId, $orderId, array $data) {
        $this->url = new Url();
        $this->url->path = 'site-integration/payment-config';
        $this->url->query = [
            'method_id' => $methodId,
            'order_id'  => $orderId,
        ];
        $this->data = $data;

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