<?php

namespace EnterMobile\Model\Partial\Cart {
    use EnterMobile\Model\Partial;

    class ProductSpinner extends Partial\Widget {
        public $widgetType = 'productSpinner';
        /** @var string */
        public $id;
        /** @var bool */
        public $buttonDataValue;
        /** @var string */
        public $buttonId;
        /** @var string */
        public $class;
        /** @var int */
        public $value;
        /** @var string */
        public $dataUrl;
        /** @var string */
        public $dataValue;
        /** @var int|null */
        public $timer;
        /** @var bool */
        public $updateState;
    }
}
