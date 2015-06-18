<?php

namespace EnterQuery\Payment;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetListByOrderNumberErp extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($geoId, $orderNumberErp) {
        $this->url = new Url();
        $this->url->path = 'v2/payment-method/get-for-order';
        $this->url->query = [
            'geo_id'     => $geoId,
            'number_erp' => $orderNumberErp,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = is_array($data) ? $data : null;
    }
}