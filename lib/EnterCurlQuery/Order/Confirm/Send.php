<?php

namespace EnterCurlQuery\Order\Confirm;

use Enter\Curl\Query;
use EnterCurlQuery\CoreQueryTrait;
use EnterCurlQuery\Url;
use EnterModel as Model;

class Send extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $phone
     */
    public function __construct($phone) {
        $this->url = new Url();
        $this->url->path = 'v2/confirm/order';
        $this->data = [
            'mobile' => $phone,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        $this->result = isset($data['result']) ? $data : $data;
    }
}