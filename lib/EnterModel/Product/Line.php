<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Line {
    /** @var string */
    public $id;
    /** @var string */
    public $token;
    /** @var string */
    public $name;
    /** @var string */
    public $image;
    /** @var int */
    public $productCount;
    /** @var int */
    public $kitCount;
    /** @var string[] */
    public $productIds = [];
    /** @var string[] */
    public $productKitIds = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('media_image', $data)) $this->image = (string)$data['media_image'];
        if (array_key_exists('product_count', $data)) $this->productCount = (int)$data['product_count'];
        if (array_key_exists('kit_count', $data)) $this->kitCount = (int)$data['kit_count'];
        if (array_key_exists('product_id_list', $data)) $this->productIds = array_map(function($id) { return (string)$id; }, (array)$data['product_id_list']);
        if (array_key_exists('kit_id_list', $data)) $this->productKitIds = array_map(function($id) { return (string)$id; }, (array)$data['kit_id_list']);
    }
}