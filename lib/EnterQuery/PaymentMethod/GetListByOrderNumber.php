<?php

namespace EnterQuery\PaymentMethod;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByOrderNumber extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $number
     * @param $regionId
     */
    public function __construct($number, $regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/payment-method/get-for-order';
        $this->url->query = [
            'geo_id' => $regionId,
            'number' => $number,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['methods'][0]['id']) ? $data : [];
    }
}