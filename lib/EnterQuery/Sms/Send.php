<?php

namespace EnterQuery\Sms;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class Send extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $phone
     * @param string|null $message
     */
    public function __construct($phone, $message = null) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/sms/send';
        $this->url->query = [
            'client_id' => 'erp',
            'token'     => '35F13D61-970A-4504-9AD6-A03B0C48D486',
            'number'    => $phone,
            'guid'      => null,
        ];

        $this->data = [
            'text' => $message,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);
        $this->result = isset($data['confirmed']) ? $data : null;
    }
}
