<?php

namespace EnterTerminal\Model\Page {
    use EnterModel as Model;

    class Search {
        /** @var string */
        public $searchPhrase;
        /** @var Model\Product[] */
        public $products = [];
        /** @var int */
        public $productCount;
        /** @var Model\Product\Sorting[] */
        public $sortings = [];
        /** @var Model\Product\Filter[] */
        public $filters = [];
    }
}
