<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByNumber extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $number
     * @param string $mobilePhone
     */
    public function __construct($number, $mobilePhone) {
        $this->url = new Url();
        $this->url->path = 'v2/order/get-by-mobile';
        $this->url->query = [
            'number' => $number,
            'mobile' => $mobilePhone,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['number']) ? $data[0] : null;
    }
}