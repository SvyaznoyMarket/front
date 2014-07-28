<?php

namespace EnterQuery\Order\Confirm;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class Check extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $phone
     * @param string $code
     */
    public function __construct($phone, $code) {
        $this->url = new Url();
        $this->url->path = 'v2/confirm/order';
        $this->data = [
            'mobile' => $phone,
            'code' => $code,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        $this->result = isset($data['result']) ? $data : null;
    }
}