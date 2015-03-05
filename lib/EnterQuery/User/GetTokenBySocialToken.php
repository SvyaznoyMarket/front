<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetTokenBySocialToken extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $type
     * @param string $id
     * @param string $accessToken
     * @param string $email
     */
    public function __construct($type, $id, $accessToken, $email) {
        $this->url = new Url();
        $this->url->path = 'v2/user/social-auth';

        $this->url->query = [
            'email'                 => $email,
            $type . '_id'           => $id,
            $type . '_access_token' => $accessToken,
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