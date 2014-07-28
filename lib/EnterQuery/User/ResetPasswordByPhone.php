<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class ResetPasswordByPhone extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $phone
     */
    public function __construct($phone) {
        $this->url = new Url();
        $this->url->path = 'v2/user/reset-password';
        $this->url->query = [
            'mobile'   => $phone,
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