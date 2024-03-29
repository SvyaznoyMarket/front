<?php

namespace EnterMobile\Model\Partial {
    use EnterMobile\Model\Partial;

    class ProductSlider extends Partial\Widget {
        public $widgetType = 'productSlider';
        /** @var int */
        public $count = 0;
        /** @var string */
        public $dataName;
        /** @var string */
        public $dataUrl;
        /** @var Partial\ProductCard[] */
        public $productCards = [];
        /** @var ProductSlider\Category[] */
        public $categories = [];
        /** @var bool */
        public $hasCategories;
        /** @var string */
        public $dataGa;
    }
}

namespace EnterMobile\Model\Partial\ProductSlider {
    class Category {
        /** @var string */
        public $name;
        /** @var string */
        public $id;
    }
}