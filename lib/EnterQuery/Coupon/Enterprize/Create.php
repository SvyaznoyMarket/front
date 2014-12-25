<?php

namespace EnterQuery\Coupon\Enterprize;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class Create extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $userToken
     * @param Model\User $user
     * @param Model\Coupon\Series $couponSeries
     */
    public function __construct($userToken, Model\User $user, Model\Coupon\Series $couponSeries) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/coupon/enter-prize';

        $this->url->query = [
            'token' => $userToken,
        ];
        $this->data = [
            'mobile'    => $user->phone,
            'email'     => $user->email,
            'name'      => $user->firstName,
            'guid'      => $couponSeries->id,
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