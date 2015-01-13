<?php

namespace EnterModel;

use EnterModel as Model;

class DeliveryType {
    /** @var string */
    public $id;
    /** @var string */
    public $token;
    /** @var string */
    public $name;
    /** @var string */
    public $shortName;
    /** @var string */
    public $description;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('short_name', $data)) $this->shortName = (string)$data['short_name'];
        if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
    }
}