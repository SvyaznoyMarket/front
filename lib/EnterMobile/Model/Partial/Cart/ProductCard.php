<?php

namespace EnterMobile\Model\Partial\Cart;

use EnterMobile\Model\Partial;

class ProductCard {
    /** @var string */
    public $name;
    /** @var string */
    public $url;
    /** @var float */
    public $price;
    /** @var string */
    public $shownPrice;
    /** @var Partial\Cart\ProductSum|null */
    public $sum;
    /** @var float */
    public $oldPrice;
    /** @var string */
    public $shownOldPrice;
    /** @var string */
    public $image;
    /** @var string */
    public $id;
    /** @var Partial\Cart\ProductSpinner|null */
    public $cartSpinner;
    /** @var Partial\Cart\ProductDeleteButton|null */
    public $cartDeleteButton;
}