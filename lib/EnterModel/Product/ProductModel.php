<?php

namespace EnterModel\Product;

use EnterModel as Model;

class ProductModel {
    /** @var Model\Product\ProductModel\Property|null */
    public $property;

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        if (!empty($data['property']) && !empty($data['items'])) $this->property = new Model\Product\ProductModel\Property($data);
    }
}