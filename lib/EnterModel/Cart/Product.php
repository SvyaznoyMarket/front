<?php

namespace EnterModel\Cart;

class Product {
    /** @var string */
    public $id;
    /** @var string */
    public $ui;
    /** @var int */
    public $quantity;
    /** @var int */
    public $price;
    /** @var float */
    public $sum;
    /** @var string */
    public $parentId;
    /** @var string */
    public $addedAt;
    /** @var array */
    public $sender = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui'];
        if (array_key_exists('quantity', $data)) $this->quantity = (int)$data['quantity'];
        if (array_key_exists('price', $data)) $this->price = (int)$data['price'];
        if (array_key_exists('sum', $data)) $this->sum = (float)$data['sum'];
        if (array_key_exists('added', $data)) $this->addedAt = (string)$data['added'];
        if (isset($data['meta_data']['sender'])) $this->sender = (array)$data['meta_data']['sender'];
    }
}