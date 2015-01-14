<?php

namespace EnterQuery\Storage;

use Enter\Curl\Query;
use EnterQuery\CorePrivateQueryTrait;
use EnterQuery\Url;

class GetItemByKey extends Query {
    use CorePrivateQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __construct($key, $value) {
        $this->url = new Url();
        $this->url->path = 'storage/get';
        $this->url->query = [
            $key => $value,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['value']) ? json_decode($data['value'], true) : [];
    }
}