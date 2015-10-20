<?php

namespace EnterModel\Product\Property;

class Option {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $value;
    /** @var string */
    public $hint = '';

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('value', $data)) $this->value = (string)$data['value'];
        if (array_key_exists('hint', $data)) $this->hint = (string)$data['hint'];
    }
}