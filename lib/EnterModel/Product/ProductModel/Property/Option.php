<?php

namespace EnterModel\Product\ProductModel\Property;

use EnterModel as Model;

class Option {
    /** @var mixed */
    public $value;
    /** @var Model\Product\ProductModel\Property\Option\Product|null */
    public $product;
    /** @var string */
    public $shownValue = '';

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('value', $data)) $this->value = $data['value'];
        if (isset($data['product']['id'])) $this->product = new Model\Product\ProductModel\Property\Option\Product($data['product']);

        if (in_array($this->value, ['false', false], true)) {
            $this->shownValue = 'нет';
        } else if (in_array($this->value, ['true', true], true)) {
            $this->shownValue = 'да';
        } else {
            $this->shownValue = (string)$this->value;
        }
    }
}