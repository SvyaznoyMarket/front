<?php

namespace EnterModel\Product\Media;

class Photo3d {
    /** @var string */
    public $productId;
    /** @var string */
    public $source;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('source', $data)) $this->source = (string)$data['source'];
        if (array_key_exists('product_id', $data)) $this->productId = (string)$data['product_id'];
    }
}