<?php

namespace EnterModel\Order;

use EnterModel as Model;

class User {
    /** @var int */
    public $sex = 0;

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        if (isset($data['sex'])) $this->sex = (int)$data['sex'];
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data) {
        if (array_key_exists('sex', $data)) $this->sex = (int)$data['sex'];
    }
}