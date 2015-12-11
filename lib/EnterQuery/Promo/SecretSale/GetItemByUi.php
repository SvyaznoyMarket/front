<?php

namespace EnterQuery\Promo\SecretSale;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemByUi extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    public function __construct($uid) {
        $this->url = new Url();
        $this->url->path = 'api/promo-sale/get';
        $this->url->query = [
            'uid' => [$uid],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        if (isset($data['result'][0]['uid'])) {
            $this->result = $data['result'][0];
        }
    }
}