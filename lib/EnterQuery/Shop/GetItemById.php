<?php

namespace EnterQuery\Shop;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetItemById extends Query {
    use ScmsQueryTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param string $id
     */
    public function __construct($id) {
        $this->url = new Url();
        $this->url->path = 'shop/get';
        $this->url->query = [
            'id' => [$id],
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result'][0]['id']) ? $data['result'][0] : null;
    }
}