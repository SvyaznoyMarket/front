<?php
namespace EnterModel\Cart\Split\Order;

class Product {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $namePrefix;
    /** @var string|null */
    public $webName;
    /** @var string|null */
    public $url;
    /** @var string|null */
    public $image;
    /** @var string|null */
    public $price;
    /** @var string|null */
    public $originalPrice;
    /** @var string|null */
    public $sum;
    /** @var string|null */
    public $quantity;
    /** @var string|null */
    public $stockQuantity;

    public function __construct($data = []) {
        if (isset($data['id'])) {
            $this->id = (string)$data['id'];
        }

        if (isset($data['ui'])) {
            $this->ui = (string)$data['ui'];
        }

        if (isset($data['name'])) {
            $this->name = (string)$data['name'];
        }

        if (isset($data['prefix'])) {
            $this->namePrefix = (string)$data['prefix'];
        }

        if (isset($data['name_web'])) {
            $this->webName = (string)$data['name_web'];
        }

        if (isset($data['url'])) {
            $this->url = (string)$data['url'];
        }

        if (isset($data['image'])) {
            $this->image = (string)$data['image'];
        }

        if (isset($data['price'])) {
            $this->price = (string)$data['price'];
        }

        if (isset($data['original_price'])) {
            $this->originalPrice = (string)$data['original_price'];
        }

        if (isset($data['sum'])) {
            $this->sum = (string)$data['sum'];
        }

        if (isset($data['quantity'])) {
            $this->quantity = (string)$data['quantity'];
        }

        if (isset($data['stock'])) {
            $this->stockQuantity = (string)$data['stock'];
        }
    }
}
