<?php

namespace EnterModel\Product\Media;

class Video {
    /** @var string */
    public $productId;
    /** @var string */
    public $content;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('product_id', $data)) $this->productId = (string)$data['product_id'];
        if (array_key_exists('content', $data)) $this->content = (string)$data['content'];
    }
}