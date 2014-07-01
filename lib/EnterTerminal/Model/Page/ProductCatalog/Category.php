<?php

namespace EnterTerminal\Model\Page\ProductCatalog {
    use EnterSite\Model;

    class Category {
        /** @var \EnterModel\Product\Category */
        public $category;
        /** @var \EnterModel\Product\Catalog\Config */
        public $catalogConfig;
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
