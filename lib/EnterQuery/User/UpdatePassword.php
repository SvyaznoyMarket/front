<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class UpdatePassword extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $token
     * @param string $password
     * @param string $newPassword
     */
    public function __construct($token, $password, $newPassword) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/user/change-password';
        $this->url->query = [
            'token'        => $token,
            'password'     => $password,
            'new_password' => $newPassword,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['confirmed']) ? $data : null;
    }
}