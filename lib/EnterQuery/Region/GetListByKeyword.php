<?php

namespace EnterQuery\Region;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;

class GetListByKeyword extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $keyword
     */
    public function __construct($keyword) {
        $this->url = new Url();
        $this->url->path = 'v2/geo/autocomplete';
        $this->url->query = [
            'letters' => $keyword,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]['id']) ? $data : [];
    }
}