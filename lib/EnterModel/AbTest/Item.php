<?php

namespace EnterModel\AbTest;

class Item {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $token;
    /** @var int */
    public $traffic;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('uid', $data)) $this->id = (string)$data['uid'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
        if (array_key_exists('traffic', $data)) $this->traffic = (int)$data['traffic'];
    }
}