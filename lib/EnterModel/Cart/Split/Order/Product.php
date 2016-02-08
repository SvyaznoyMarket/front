<?php
namespace EnterModel\Cart\Split\Order;

use EnterModel as Model;

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
    /** @var array */
    public $sender;
    /** @var Model\MediaList */
    public $media;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->ui = $data['ui'] ? (string)$data['ui'] : null;
        $this->image = isset($data['image']) ? (string)$data['image'] : null;
        $this->price = $data['price'] ? (string)$data['price'] : null;
        $this->originalPrice = $data['original_price'] ? (string)$data['original_price'] : null;
        $this->sum = $data['sum'] ? (string)$data['sum'] : null;
        $this->quantity = (int)$data['quantity'];
        $this->stockQuantity = (int)$data['stock'];
        $this->sender = isset($data['meta_data']['sender']['name']) ? $data['meta_data']['sender'] : null;
        $this->media = new Model\MediaList();
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'             => $this->id,
            'ui'             => $this->ui,
            'name'           => $this->name,
            'prefix'         => $this->namePrefix,
            'name_web'       => $this->webName,
            'url'            => $this->url,
            'image'          => $this->image,
            'price'          => $this->price,
            'original_price' => $this->originalPrice,
            'sum'            => $this->sum,
            'quantity'       => $this->quantity,
            'stock'          => $this->stockQuantity,
            'meta_data'      => $this->sender ? ['sender' => $this->sender] : [],
        ];
    }
}
