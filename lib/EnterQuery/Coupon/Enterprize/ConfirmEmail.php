<?php

namespace EnterQuery\Coupon\Enterprize;

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
     * @param string $promoToken
     * @param string|null $code
     */
    public function __construct($userToken, $email, $promoToken, $code = null) {
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