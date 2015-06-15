<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemById extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $clientId
     * @param string $userToken
     * @param string $orderId
     */
    public function __construct($clientId = 'mobile', $userToken, $orderId) {
        $this->url = new Url();
        $this->url->path = 'v2/order/get';
        $this->url->query = [
            'client_id' => $clientId,
            'token' => $userToken,
            'id' => $orderId
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['number']) ? $data[0] : null;
    }
}