<?php

namespace EnterQuery\Product\Relation\SearchToItems;

use Enter\Curl\Query;
use EnterQuery\RetailRocketQueryTrait;
use EnterQuery\RetailRocketUrl;
use EnterModel as Model;

class GetIdListByKeyword extends Query {
    use RetailRocketQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $keyword
     */
    public function __construct($keyword) {
        $this->url = new RetailRocketUrl();
        $this->url->method = 'Recomendation/SearchToItems';
        $this->url->query['keyword'] = $keyword;

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