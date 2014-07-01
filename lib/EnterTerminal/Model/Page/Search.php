<?php

namespace EnterTerminal\Model\Page {
    use EnterSite\Model;

    class Search {
        /** @var string */
        public $searchPhrase;
        /** @var \EnterModel\Product[] */
        public $products = [];
        /** @var int */
        public $productCount;
        /** @var \EnterModel\Product\Sorting[] */
        public $sortings = [];
        /** @var \EnterModel\Product\Filter[] */
        public $filters = [];
    }
}
