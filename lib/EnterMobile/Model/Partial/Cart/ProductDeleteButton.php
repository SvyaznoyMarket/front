<?php

namespace EnterMobile\Model\Partial\Cart {
    use EnterMobile\Model\Partial;

    class ProductDeleteButton extends Partial\Widget {
        public $widgetType = 'productDeleteButton';
        /** @var string */
        public $id;
        /** @var string */
        public $url;
        /** @var string */
        public $class;
        /** @var string */
        public $dataUrl;
        /** @var string */
        public $dataValue;
        /** @var string */
        public $spinnerSelector;
    }
}
