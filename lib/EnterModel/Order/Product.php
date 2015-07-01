<?php

namespace EnterModel\Order;

use EnterModel as Model;

class Product extends Model\Product {
    /** @var string */
    public $id;
    /** @var float */
    public $price;
    /** @var float */
    public $sum;
    /** @var int */
    public $quantity;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('price', $data)) $this->price = (float)$data['price'];
        if (array_key_exists('quantity', $data)) $this->quantity = (int)$data['quantity'];
        if (array_key_exists('sum', $data)) $this->sum = (float)$data['sum'];
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('price', $data)) $this->price = (float)$data['price'];
        if (array_key_exists('quantity', $data)) $this->quantity = (int)$data['quantity'];
        if (array_key_exists('sum', $data)) $this->sum = (float)$data['sum'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('article', $data)) $this->article = (string)$data['article'];
        if (array_key_exists('link', $data)) $this->link = (string)$data['link'];
        if (isset($data['category']['name'])) {
            $this->category = new Model\Product\Category();
            $this->category->fromArray($data['category']);
        }
    }
}