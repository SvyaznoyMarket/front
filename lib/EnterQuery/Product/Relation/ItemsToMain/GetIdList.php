<?php

namespace EnterQuery\Product\Relation\ItemsToMain;

use Enter\Curl\Query;
use EnterQuery\RetailRocketQueryTrait;
use EnterQuery\RetailRocketUrl;
use EnterModel as Model;

class GetIdList extends Query {
    use RetailRocketQueryTrait;

    /** @var array */
    protected $result;

    public function __construct() {
        $this->url = new RetailRocketUrl();
        $this->url->method = 'Recomendation/ItemsToMain';

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