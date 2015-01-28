<?php

namespace EnterModel\Product\Filter;

use EnterModel as Model;

class Option {
    /** @var string */
    public $id;
    /** @var string */
    public $token;
    /** @var string */
    public $name;
    /** @var int */
    public $quantity;
    /** @var int */
    public $globalQuantity;
    /** @var Model\Media|null */
    public $media;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('quantity', $data)) $this->quantity = (int)$data['quantity'];
        if (array_key_exists('global', $data)) $this->globalQuantity = (int)$data['global'];
    }
}