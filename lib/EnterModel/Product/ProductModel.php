<?php

namespace EnterModel\Product;

use EnterModel as Model;

class ProductModel {
    /** @var Model\Product\ProductModel\Property[] */
    public $properties = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['property'][0])) {
            foreach ($data['property'] as $propertyItem) {
                $this->properties[] = new Model\Product\ProductModel\Property($propertyItem);
            }
        }
    }
}