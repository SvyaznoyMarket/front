<?php

namespace EnterQuery\Certificate;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class Check extends Query {
    use CoreQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $code
     * @param string|null $pin
     */
    public function __construct($code, $pin = null) {
        $this->url = new Url();
        $this->url->path = 'v2/certificate/check';
        $this->url->query = [
            'code' => $code,
        ];
        if ($pin) {
            $this->url->query['pin'] = $pin;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['face']) ? $data : null;
    }
}