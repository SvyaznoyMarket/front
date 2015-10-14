<?php

namespace EnterModel\Product\ProductModel\Property;

use EnterModel as Model;

class Option {
    /** @var string */
    public $value = '';
    /** @var Model\Product\ProductModel\Property\Option\Product|null */
    public $product;

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        if (isset($data['property_value'])) $this->value = (string)$data['property_value'];
        if (isset($data['product'])) $this->product = new Model\Product\ProductModel\Property\Option\Product($data['product']);
    }
}