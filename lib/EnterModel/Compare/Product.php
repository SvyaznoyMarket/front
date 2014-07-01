<?php

namespace EnterModel\Compare;

class Product {
    /** @var string */
    public $id;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
    }
}