<?php

namespace EnterModel\Product\ProductModel;

use EnterModel as Model;

class Property {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $unit;
    /** @var bool */
    public $isImage;
    /** @var Model\Product\ProductModel\Property\Option[] */
    public $options = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('unit', $data)) $this->unit = $data['unit'] ? (string)$data['unit'] : null;
        if (array_key_exists('is_image', $data)) $this->isImage = (bool)$data['is_image'];
        if (isset($data['option'][0])) {
            foreach ($data['option'] as $optionItem) {
                $this->options[] = new Model\Product\ProductModel\Property\Option($optionItem);
            }
        }
    }
}