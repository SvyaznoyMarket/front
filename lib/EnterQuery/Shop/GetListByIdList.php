<?php

namespace EnterQuery\Shop;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetListByIdList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $ids
     */
    public function __construct(array $ids) {
        $this->url = new Url();
        $this->url->path = 'shop/get';
        $this->url->query = [
            'id' => $ids,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result'][0]['id']) ? $data['result'] : [];
    }
}