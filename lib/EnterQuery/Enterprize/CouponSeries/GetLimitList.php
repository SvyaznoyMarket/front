<?php

namespace EnterQuery\Enterprize\CouponSeries;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetLimitList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    public function __construct() {
        $this->url = new Url();
        $this->url->path = 'v2/coupon/limits';

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = (isset($data['detail']) && is_array(isset($data['detail']))) ? $data['detail'] : [];
    }
}