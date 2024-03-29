<?php

namespace EnterQuery\User;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class ConfirmEmail extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $userToken
     * @param string $email
     * @param string|null $code
     * @param string $promoToken
     */
    public function __construct($userToken, $email, $code = null, $promoToken) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/confirm/email';

        $this->url->query = [
            'token' => $userToken,
        ];
        $this->data = [
            'email'    => $email,
            'template' => $promoToken,
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