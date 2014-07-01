<?php

namespace EnterModel;

class Brand {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $token;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
    }
}