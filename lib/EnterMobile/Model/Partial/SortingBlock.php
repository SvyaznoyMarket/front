<?php

namespace EnterMobile\Model\Partial {

    use EnterMobile\Model\Partial;

    class SortingBlock extends Partial\Widget {
        public $widgetType = 'productSorting';
        /** @var SortingBlock\Sorting */
        public $sorting;
        /** @var SortingBlock\Sorting[] */
        public $sortings = [];
    }
}

namespace EnterMobile\Model\Partial\SortingBlock {
    class Sorting {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $dataValue;
        /** @var string */
        public $dataGa;
        /** @var bool */
        public $isActive;
    }
}