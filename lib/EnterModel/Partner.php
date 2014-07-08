<?php

namespace EnterModel;

class Partner {
    /** @var string */
    public $token;
    /** @var string */
    public $name;
    /** @var string[] */
    public $cookie = [];

    public function __construct(array $data = []) {
        if (isset($data['token'])) $this->token = (string)$data['token'];
        if (isset($data['name'])) $this->name = (string)$data['name'];
    }
}