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

    public function __construct(Model\Coupon\Series $couponSeries, Model\User $user, $promoToken = null) {
        $this->url = new Url();
        $this->url->path = 'v2/coupon/send';

        $this->url->query = [];
        $this->data = [
            'guid' => $couponSeries->id,
        ];
        if ($promoToken) {
            $this->data['promo'] = $promoToken;
        }
        if ($user->email) {
            $this->data['email'] = $user->email;
        }
        if ($user->phone) {
            $this->data['mobile'] = $user->phone;
        }
        if ($user->ui) {
            $this->data['request_uid'] = $user->ui;
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