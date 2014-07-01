<?php

namespace EnterTerminal\Model\Page {
    use EnterSite\Model;

    class Cart {
        /** @var float */
        public $sum;
        /** @var \EnterModel\Product[] */
        public $products = [];
    }
}
