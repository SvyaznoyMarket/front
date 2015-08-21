<?php

namespace EnterQuery\Product;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetRandomUiList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $criteria
     */
    public function __construct(array $criteria) {
        $criteria += [];

        $this->url = new Url();
        $this->url->path = 'phalcon/get-random-product';
        if (null !== $criteria['discount']) {
            $this->url->query['discount'] = $criteria['discount'];
        }
        if (null !== $criteria['shopId']) {
            $this->url->query['shop_id'] = $criteria['shopId'];
        }
        if (null !== $criteria['price']) {
            $this->url->query['price'] = $criteria['price'];
        }
        if (null !== $criteria['limit']) {
            $this->url->query['limit'] = $criteria['limit'];
        }

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