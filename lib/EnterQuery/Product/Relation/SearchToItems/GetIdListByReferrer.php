<?php

namespace EnterQuery\Product\Relation\SearchToItems;

use Enter\Curl\Query;
use EnterQuery\RetailRocketQueryTrait;
use EnterQuery\RetailRocketUrl;
use EnterModel as Model;

class GetIdListByReferrer extends Query {
    use RetailRocketQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $referrer
     */
    public function __construct($referrer) {
        $this->url = new RetailRocketUrl();
        $this->url->method = 'Recomendation/SearchToItems';
        $this->url->query['referrer'] = $referrer;

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