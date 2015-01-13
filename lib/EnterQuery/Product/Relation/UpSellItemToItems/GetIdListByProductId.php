<?php

namespace EnterQuery\Product\Relation\UpSellItemToItems;

use Enter\Curl\Query;
use EnterQuery\RetailRocketQueryTrait;
use EnterQuery\RetailRocketUrl;
use EnterModel as Model;

class GetIdListByProductId extends Query {
    use RetailRocketQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $productId
     */
    public function __construct($productId) {
        $this->url = new RetailRocketUrl();
        $this->url->method = 'Recomendation/UpSellItemToItems';
        $this->url->itemId = $productId;

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