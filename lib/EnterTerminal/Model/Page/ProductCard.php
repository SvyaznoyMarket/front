<?php

namespace EnterTerminal\Model\Page {
    use EnterSite\Model;

    class ProductCard {
        /** @var \EnterModel\Product */
        public $product;
        /** @var \EnterModel\Product\Review[] */
        public $reviews = [];
    }
}
