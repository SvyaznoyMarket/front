<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetTokenByPhone extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $phone
     * @param $password
     */
    public function __construct($phone, $password) {
        $this->url = new Url();
        $this->url->path = 'v2/user/auth';
        $this->url->query = [
            'mobile'   => $phone,
            'password' => $password,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['token']) ? $data : null;
    }
}