<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByUserToken extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result = [];

    /**
     * @param string $token
     * @param int|null $offset
     * @param int|null $limit
     */
    public function __construct($token, $offset = null, $limit = null) {
        $this->url = new Url();
        $this->url->path = 'v2/order/get-limited';
        $this->url->query = [
            'token' => $token,
        ];
        if (null !== $offset) {
            $this->url->query['offset'] = $offset;
        }
        if (null !== $limit) {
            $this->url->query['limit'] = $limit;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['orders']) ? $data : [];
    }
}