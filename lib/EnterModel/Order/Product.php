<?php

namespace EnterModel\Order;

use EnterModel as Model;

class Product {
    /** @var string */
    public $id;
    /** @var int */
    public $price;
    /** @var int */
    public $sum;
    /** @var int */
    public $quantity;
    /** @var array|null */
    public $sender;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('price', $data)) $this->price = (int)$data['price'];
        if (array_key_exists('quantity', $data)) $this->quantity = (int)$data['quantity'];
        if (array_key_exists('sum', $data)) $this->sum = (int)$data['sum'];
        if (isset($data['meta_data']['sender'])) $this->sender = (array)$data['meta_data']['sender'];
    }
}