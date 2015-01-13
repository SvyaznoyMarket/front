<?php

namespace EnterQuery\Product\Rating;

use Enter\Curl\Query;
use EnterQuery\ReviewQueryTrait;
use EnterQuery\Url;

class GetListByProductUiList extends Query {
    use ReviewQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string[] $productUis
     */
    public function __construct(array $productUis) {
        $this->url = new Url();
        $this->url->path = 'scores-list';
        $this->url->query = [
            'product_ui' => implode(',', $productUis),
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