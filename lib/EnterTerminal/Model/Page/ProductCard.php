<?php

namespace EnterTerminal\Model\Page {
    use EnterModel as Model;

    class ProductCard {
        /** @var Model\Product */
        public $product;
        /** @var Model\Product\Review[] */
        public $reviews = [];
        /** @var Model\Product[] */
        public $kitProducts = [];
    }
}
