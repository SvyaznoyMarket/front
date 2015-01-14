<?php

namespace EnterQuery\Storage;

use Enter\Curl\Query;
use EnterQuery\CorePrivateQueryTrait;
use EnterQuery\Url;

class DeleteItemByKey extends Query {
    use CorePrivateQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __construct($key, $value) {
        $this->url = new Url();
        $this->url->path = 'storage/delete';
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

        $this->result = $data;
    }
}