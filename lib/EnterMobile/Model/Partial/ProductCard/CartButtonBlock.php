<?php

namespace EnterMobile\Model\Partial\ProductCard;

use EnterMobile\Model\Partial;

class CartButtonBlock extends Partial\Widget {
    public $widgetType = 'productButtonBlock';

    /** @var Partial\Cart\ProductLink|null */
    public $cartLink;
    /** @var Partial\Cart\ProductButton|null */
    public $cartButton;
    /** @var Partial\Cart\ProductSpinner|null */
    public $cartSpinner;
    /** @var Partial\Cart\ProductQuickButton|null */
    public $cartQuickButton;
}