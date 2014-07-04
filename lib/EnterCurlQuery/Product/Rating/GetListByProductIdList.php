<?php

namespace EnterCurlQuery\Product\Rating;

use Enter\Curl\Query;
use EnterCurlQuery\ReviewQueryTrait;
use EnterCurlQuery\Url;

class GetListByProductIdList extends Query {
    use ReviewQueryTrait;

    /** @var array */
    protected $result;

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

        $this->result = isset($data['product_scores'][0]['product_id']) ? $data['product_scores'] : [];
    }
}