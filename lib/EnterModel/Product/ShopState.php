<?php

namespace EnterModel\Product;

use EnterModel as Model;

class ShopState {
    /** @var Model\Shop|null */
    public $shop;
    /** @var int */
    public $quantity;
    /**
     * Количество товара на витрине
     *
     * @var int
     */
    public $showroomQuantity;
    /** @var bool */
    public $isInShowroomOnly;
}