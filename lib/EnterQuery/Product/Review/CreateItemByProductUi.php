<?php

namespace EnterQuery\Product\Review;

use Enter\Curl\Query;
use EnterQuery\ReviewQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class CreateItemByProductUi extends Query {
    use ReviewQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param $productUi
     * @param Model\Product\Review $review
     */
    public function __construct($productUi, Model\Product\Review $review) {
        $this->url = new Url();
        $this->url->path = 'add';
        $this->url->query = [
            'product_ui' => $productUi,
        ];
        $this->data = [
            'advantage'     => $review->pros,
            'disadvantage'  => $review->cons,
            'extract'       => $review->extract,
            'score'         => $review->score,
            'author_name'   => $review->author,
            'author_email'  => $review->email,
            'date'          => $review->createdAt ? $review->createdAt->format('Y-m-d') : null,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}