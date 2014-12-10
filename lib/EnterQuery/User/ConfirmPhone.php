<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class ConfirmPhone extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $userToken
     * @param string $phone
     * @param string|null $code
     */
    public function __construct($userToken, $phone, $code = null) {
        $this->url = new Url();
        $this->url->path = 'v2/confirm/mobile';

        $this->url->query = [
            'token' => $userToken,
        ];
        $this->data = [
            'mobile' => $phone,
        ];

        if ($code) {
            $this->data['code'] = $code;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}