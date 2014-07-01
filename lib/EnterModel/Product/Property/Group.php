<?php

namespace EnterModel\Product\Property;

class Group {
    /** @var string */
    public $id;
    /** @var string */
    public $name;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
    }
}