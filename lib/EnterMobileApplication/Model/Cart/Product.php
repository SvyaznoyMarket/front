<?php

namespace EnterMobileApplication\Model\Cart;

class Product {
    /** @var string */
    public $id;
    /** @var string */
    public $webName;
    /** @var string */
    public $namePrefix;
    /** @var string */
    public $name;
    /** @var mixed */
    public $price;
    /** @var \EnterModel\Product\Media */
    public $media;
    /** @var int */
    public $quantity;
    /** @var mixed */
    public $sum;
}