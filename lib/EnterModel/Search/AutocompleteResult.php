<?php

namespace EnterModel\Search {
    use EnterModel as Model;

    class AutocompleteResult {
        /** @var Category[] */
        public $categories = [];
        /** @var Product[] */
        public $products = [];

        /**
         * @param mixed $data
         */
        public function __construct($data = []) {
            if (isset($data['products'][0])) {
                foreach ($data['products'] as $product) {
                    $this->products[] = new Product($product);
                }
            }

            if (isset($data['categories'][0])) {
                foreach ($data['categories'] as $category) {
                    $this->categories[] = new Category($category);
                }
            }
        }
    }
}