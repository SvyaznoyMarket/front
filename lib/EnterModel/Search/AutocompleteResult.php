<?php

namespace EnterModel\Search {
    use EnterModel as Model;

    class AutocompleteResult {
        /** @var Category[] */
        public $categories = [];
        /** @var Product[] */
        public $products = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            foreach (isset($data['1']) && is_array($data['1']) ? $data['1'] : [] as $product) {
                $this->products[] = new Product($product);
            }

            foreach (isset($data['3']) && is_array($data['3']) ? $data['3'] : [] as $category) {
                $this->categories[] = new Category($category);
            }
        }
    }
}