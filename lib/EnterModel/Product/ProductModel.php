<?php

namespace EnterModel\Product;

use EnterModel as Model;

class ProductModel {
    /** @var string */
    public $ui = '';
    /** @var Model\Product\ProductModel\Property|null */
    public $property;

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        if (isset($data['uid'])) $this->ui = (string)$data['uid'];
        if (!empty($data['property']) && !empty($data['items'])) $this->property = new Model\Product\ProductModel\Property($data);
    }
}