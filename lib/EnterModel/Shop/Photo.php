<?php

namespace EnterModel\Shop;

/**
 * @deprecated
 */
class Photo {
    /** @var string */
    public $source;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('source', $data)) $this->source = (string)$data['source'];
    }
}