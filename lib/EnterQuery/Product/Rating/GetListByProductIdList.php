<?php

namespace EnterQuery\Product\Rating;

use Enter\Curl\Query;
use EnterQuery\ReviewQueryTrait;
use EnterQuery\Url;

class GetListByProductIdList extends Query {
    use ReviewQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $productIds
     */
    public function __construct(array $productIds) {
        $this->url = new Url();
        $this->url->path = 'scores-list';
        $this->url->query = [
            'product_list' => implode(',', $productIds),
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['product_scores'][0]) ? $data['product_scores'] : [];
    }
}