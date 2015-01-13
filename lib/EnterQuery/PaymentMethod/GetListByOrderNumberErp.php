<?php

namespace EnterQuery\PaymentMethod;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByOrderNumberErp extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $numberErp
     * @param $regionId
     */
    public function __construct($numberErp, $regionId) {
        $this->url = new Url();
        $this->url->path = 'v2/payment-method/get-for-order';
        $this->url->query = [
            'geo_id'     => $regionId,
            'number_erp' => $numberErp,
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