<?php

namespace EnterQuery\Product\Relation\PersonalRecommendation;

use Enter\Curl\Query;
use EnterQuery\RetailRocketQueryTrait;
use EnterQuery\RetailRocketUrl;
use EnterModel as Model;

class GetIdListByRrUserId extends Query {
    use RetailRocketQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $rrUserId
     */
    public function __construct($rrUserId) {
        $this->url = new RetailRocketUrl();
        $this->url->method = 'Recomendation/PersonalRecommendation';
        $this->url->query['rrUserId'] = $rrUserId;

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