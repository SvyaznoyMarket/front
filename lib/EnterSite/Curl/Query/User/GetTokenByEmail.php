<?php

namespace EnterSite\Curl\Query\User;

use Enter\Curl\Query;
use EnterSite\Curl\Query\CoreQueryTrait;
use EnterSite\Curl\Query\Url;

class GetTokenByEmail extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $email
     * @param $password
     */
    public function __construct($email, $password) {
        $this->url = new Url();
        $this->url->path = 'v2/user/auth';
        $this->url->query = [
            'email'    => $email,
            'password' => $password,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['token']) ? $data['token'] : null;
    }
}