<?php

namespace EnterModel\Product\Property;

class Group {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var int */
    private $position;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) {
            $this->id = (string)$data['id'];
        } else if (array_key_exists('uid', $data)) {
            $this->id = (string)$data['uid']; // SITE-5290
        }
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('position', $data)) $this->position = (int)$data['position'];
    }
}