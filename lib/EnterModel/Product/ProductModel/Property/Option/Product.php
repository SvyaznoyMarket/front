<?php

namespace EnterModel\Product\ProductModel\Property\Option;

class Product {
    public $ui = '';
    /** @var string */
    public $id = '';
    /** @var string */
    public $name = '';
    /** @var string */
    public $link = '';
    /** @var string */
    public $token = '';

    /**
     * @param mixed $data
     */
    public function __construct($data = []) {
        if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('url', $data)) $this->link = (string)$data['url'];
        if (array_key_exists('slug', $data)) $this->token = (string)$data['slug'];
    }
}