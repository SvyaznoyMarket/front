<?php

namespace EnterMobile\Model\Partial\Cart {
    use EnterMobile\Model\Partial;

    class ProductLink extends Partial\Widget {
        public $widgetType = 'productLink';
        /** @var string */
        public $id;
        /** @var string */
        public $quantity;
        /** @var string */
        public $url;
    }
}
