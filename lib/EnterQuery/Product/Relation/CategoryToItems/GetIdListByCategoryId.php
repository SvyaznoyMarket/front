<?php

namespace EnterQuery\Product\Relation\CategoryToItems;

use Enter\Curl\Query;
use EnterQuery\RetailRocketQueryTrait;
use EnterQuery\RetailRocketUrl;
use EnterModel as Model;

class GetIdListByCategoryId extends Query {
    use RetailRocketQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $categoryId
     */
    public function __construct($categoryId) {
        $this->url = new RetailRocketUrl();
        $this->url->method = 'Recomendation/CategoryToItems';
        $this->url->itemId = $categoryId;

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