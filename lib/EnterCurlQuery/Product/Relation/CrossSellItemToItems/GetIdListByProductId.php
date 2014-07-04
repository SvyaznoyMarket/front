<?php

namespace EnterCurlQuery\Product\Relation\CrossSellItemToItems;

use Enter\Curl\Query;
use EnterCurlQuery\RetailRocketQueryTrait;
use EnterCurlQuery\RetailRocketUrl;
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
        $this->url->method = 'Recomendation/CrossSellItemToItems';
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