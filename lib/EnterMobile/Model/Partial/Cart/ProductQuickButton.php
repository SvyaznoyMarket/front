<?php

namespace EnterMobile\Model\Partial\Cart {
    use EnterMobile\Model\Partial;

    class ProductQuickButton extends Partial\Widget {
        public $widgetType = 'productQuickButton';
        /** @var string */
        public $id;
        /** @var string */
        public $url;
        /** @var string */
        public $dataUrl;
        /** @var string */
        public $dataValue;
    }
}
