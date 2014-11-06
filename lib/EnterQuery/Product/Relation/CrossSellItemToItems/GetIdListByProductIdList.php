<?php

namespace EnterQuery\Product\Relation\CrossSellItemToItems;

use Enter\Curl\Query;
use EnterQuery\RetailRocketQueryTrait;
use EnterQuery\RetailRocketUrl;
use EnterModel as Model;

class GetIdListByProductIdList extends Query {
    use RetailRocketQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $productIds
     */
    public function __construct(array $productIds) {
        $this->url = new RetailRocketUrl();
        $this->url->method = 'Recomendation/CrossSellItemToItems';
        $this->url->itemId = implode(',', $productIds);

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