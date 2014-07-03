<?php

namespace EnterModel\Subway;

use EnterModel as Model;

class Line {
    /** @var string */
    public $name;
    /** @var string */
    public $color;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('color', $data)) $this->color = (string)$data['color'];
    }
}