<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByAccessToken extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $accessToken
     */
    public function __construct($accessToken) {
        $this->url = new Url();
        $this->url->path = 'v2/order/get-by-token';
        $this->url->query = [
            'token' => $accessToken,
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