<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Kit {
    /** @var string */
    public $id;
    /** @var int */
    public $count;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('count', $data)) $this->count = (int)$data['count'];
    }
}