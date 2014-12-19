<?php

namespace EnterQuery\Coupon;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class Send extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $transactionId
     * @param Model\Coupon\Series $couponSeries
     * @param Model\User $user
     * @param null $promoToken
     */
    public function __construct($transactionId, Model\Coupon\Series $couponSeries, Model\User $user, $promoToken = null) {
        $this->url = new Url();
        $this->url->path = 'v2/coupon/send';

        $this->url->query = [];
        $this->data = [
            'guid'        => $couponSeries->id,
            'request_uid' => $transactionId,
            'email'       => $user->email,
            'mobile'      => $user->phone,
        ];
        if ($promoToken) {
            $this->data['promo'] = $promoToken;
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