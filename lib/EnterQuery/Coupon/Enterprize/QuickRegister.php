<?php

namespace EnterQuery\Coupon\Enterprize;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class QuickRegister extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $userToken
     * @param \EnterModel\User $user
     */
    public function __construct($userToken, Model\User $user) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/coupon/quick-register-in-enter-prize';

        $this->url->query = [
            'token' => $userToken,
        ];
        $this->data = [
            'mobile'    => $user->phone,
            'email'     => $user->email,
            'name'      => trim(implode(' ', [$user->firstName, $user->lastName])),
            'agree'     => true,
        ];

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