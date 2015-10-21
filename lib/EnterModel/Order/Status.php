<?php

namespace EnterModel\Order;

use EnterModel as Model;

class Status {
    /** @var string */
    public $id;
    /** @var string */
    public $name;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->id = isset($data['id']) ? (string)$data['id'] : null;
        $this->name = isset($data['name']) ? (string)$data['name'] : null;
    }
}