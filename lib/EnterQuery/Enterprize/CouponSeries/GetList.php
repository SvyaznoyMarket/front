<?php

namespace EnterQuery\Enterprize\CouponSeries;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $memberType
     */
    public function __construct($memberType) {
        $this->url = new Url();
        $this->url->path = 'v2/coupon/get';

        if ($memberType) {
            $this->url->query = [
                'member_type' => $memberType,
            ];
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['uid']) ? $data : [];
    }
}