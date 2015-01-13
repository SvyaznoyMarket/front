<?php

namespace EnterQuery\Subscribe;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetListByUserToken extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result = [];

    /**
     * @param $userToken
     */
    public function __construct($userToken) {
        $this->url = new Url();
        $this->url->path = 'v2/subscribe/get';
        $this->url->query = [
            'token' => $userToken,
        ];

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