<?php

namespace EnterTerminal\Model\Page {
    use EnterModel\Shop;

    class Config {
        /** @var \EnterModel\Shop */
        public $shop;
        /** @var array */
        public $info;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('shop_id', $data)) {
                $this->shop = new Shop();
                $this->shop->id = (string)$data['shop_id'];
            }

            unset($data['shop_id']);
            $this->info = $data;
        }
    }
}