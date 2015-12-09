<?php

namespace EnterQuery\Promo\SecretSale;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result = [];

    public function __construct() {
        $this->url = new Url();
        $this->url->path = 'api/promo-sale/get';

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        if (isset($data['result'][0]['uid'])) {
            $this->result = $data['result'];
        }
    }
}