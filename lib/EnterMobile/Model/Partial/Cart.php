<?php

namespace EnterMobile\Model\Partial;

use EnterMobile\Model\Partial;

class Cart extends Partial\Widget {
    public $widgetType = 'cart';
    /** @var float */
    public $sum;
    /** @var string */
    public $shownSum;
    /** @var int */
    public $quantity;
    /** @var string */
    public $shownQuantity;
    /** @var Partial\DirectCredit|null */
    public $credit;
    /** @var string */
    public $orderUrl;
    /** @var string */
    public $orderDataGa;
}