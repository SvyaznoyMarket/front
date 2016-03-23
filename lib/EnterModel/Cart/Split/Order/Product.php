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
    /** @var int|null */
    public $quantity;
    /** @var int|null */
    public $stockQuantity;
    /** @var array */
    public $sender;
    /** @var Model\MediaList */
    public $media;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        if (isset($data['id'])) $this->id = (string)$data['id'];
        if (isset($data['ui'])) $this->ui = (string)$data['ui'];
        if (isset($data['image'])) $this->image = (string)$data['image'];
        if (isset($data['price'])) $this->price = (string)$data['price'];
        if (isset($data['original_price'])) $this->originalPrice = (string)$data['original_price'];
        if (isset($data['sum'])) $this->sum = (string)$data['sum'];
        if (isset($data['quantity'])) $this->quantity = (int)$data['quantity'];
        if (isset($data['stock'])) $this->stockQuantity = (int)$data['stock'];
        if (isset($data['meta_data']['sender']['name'])) $this->sender = $data['meta_data']['sender'];
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
