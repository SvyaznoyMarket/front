<?php

namespace EnterQuery\Storage;

use Enter\Curl\Query;
use EnterQuery\CorePrivateQueryTrait;
use EnterQuery\Url;

class SetItemByKey extends Query {
    use CorePrivateQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $key
     * @param mixed $value
     * @param array $data
     */
    public function __construct($key, $value, array $data) {
        $this->url = new Url();
        $this->url->path = 'storage/post';
        $this->url->query = [
            $key => $value,
        ];
        $this->data = $data;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}