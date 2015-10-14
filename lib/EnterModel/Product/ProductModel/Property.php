<?php

namespace EnterModel\Product\ProductModel;

use EnterModel as Model;

class Property {
    /** @var string */
    public $id = '';
    /** @var string */
    public $name = '';
    /** @var Model\Product\ProductModel\Property\Option[] */
    public $options = [];

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        if (isset($data['property']['id'])) $this->id = (string)$data['property']['id'];
        if (isset($data['property']['name'])) $this->name = (string)$data['property']['name'];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->options[] = new Model\Product\ProductModel\Property\Option($item);
            }
        }
    }
}