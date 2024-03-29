<?php

namespace EnterModel\Cart;

class Product {
    /** @var string */
    public $id;
    /** @var string */
    public $ui;
    /** @var int */
    public $quantity;
    /** @var float */
    public $price;
    /** @var float */
    public $sum;
    /** @var string */
    public $parentId;
    /** @var string */
    public $addedAt;
    /** @var array */
    public $sender = [];
    /** @var mixed */
    public $clientMeta;
    /** @var \EnterModel\Product|null */
    public $product;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('ui', $data)) {
            $this->ui = (string)$data['ui'];
        } else if (array_key_exists('uid', $data)) {
            $this->ui = (string)$data['uid'];
        }
        if (array_key_exists('quantity', $data)) $this->quantity = (int)$data['quantity'];
        if (array_key_exists('price', $data)) $this->price = (float)$data['price'];
        if (array_key_exists('sum', $data)) $this->sum = (float)$data['sum'];
        if (array_key_exists('added', $data)) $this->addedAt = (string)$data['added'];
        if (array_key_exists('sender', $data)) $this->sender = (array)$data['sender'];
        if (array_key_exists('meta', $data)) $this->clientMeta = $data['meta'];
    }
}