<?php

namespace EnterModel\Product;

class Rating {
    /** @var string */
    public $productId;
    /** @var float */
    public $score;
    /** @var float */
    public $starScore;
    /** @var int */
    public $reviewCount;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('product_id', $data)) $this->productId = (string)$data['product_id'];
        if (array_key_exists('score', $data)) $this->score = (float)$data['score'];
        if (array_key_exists('star_score', $data)) $this->starScore = (float)$data['star_score'];
        if (array_key_exists('num_reviews', $data)) $this->reviewCount = (int)$data['num_reviews'];
    }
}