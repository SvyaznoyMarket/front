<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetDescriptionListByUiList extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $uis
     */
    public function __construct(array $uis) {
        $this->url = new Url();
        $this->url->path = 'product/get-description';
        $this->url->query = [
            'uid' => $uis,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]) ? $data : [];
    }
}