<?php

namespace EnterMobile\Model\Partial\Cart {
    use EnterMobile\Model\Partial;

    class ProductSum extends Partial\Widget {
        public $widgetType = 'cartProductSum';
        /** @var float */
        public $value;
        /** @var string */
        public $shownValue;
    }
}
