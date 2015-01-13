<?php

namespace EnterQuery\Coupon\Series;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;

class GetItemByUi extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param $ui
     */
    public function __construct($ui) {
        $this->url = new Url();
        $this->url->path = 'v2/coupon/get';

        $this->url->query = [
            'uid' => $ui,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['uid']) ? $data[0] : null;
    }
}