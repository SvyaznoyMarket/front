<?php

namespace EnterQuery\Seller;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetItemByUi extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($ui) {
        $this->url = new Url();
        $this->url->path = 'v2/partner/get';
        $this->url->query = ['ui' => $ui];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        $this->result = isset($data[0]['id']) ? $data[0] : null;
    }
}