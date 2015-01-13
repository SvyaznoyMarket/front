<?php

namespace EnterQuery\Coupon;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetListByUserToken extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $userToken
     */
    public function __construct($userToken) {
        $this->url = new Url();
        $this->url->path = 'v2/user/get-discount-coupons';

        $this->url->query = [
            'token' => $userToken,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['detail'][0]) ? $data['detail'] : [];
    }
}