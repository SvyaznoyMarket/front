<?php

namespace EnterModel {
    use EnterModel as Model;

    class Promo {
        /** @var string */
        public $id;
        /** @var string */
        public $type;
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $image;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
            if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
            if (array_key_exists('media_image', $data)) $this->image = (string)$data['media_image'];
            if (array_key_exists('url', $data)) $this->url = $data['url'] ? (string)$data['url'] : null;

            if (0 === strpos($this->url, '/slices/')) {
                $this->type = 'ProductCatalog/Slice';
            } else if (0 === strpos($this->url, '/catalog/')) {
                $this->type = 'ProductCatalog/Category';
            } else if (0 === strpos($this->url, '/product/')) {
                $this->type = 'ProductCard';
            } else if (0 === strpos($this->url, '/products/set')) {
                $this->type = 'ProductSet';
            } else {
                $this->type = 'Content';

                // FIXME
                try {
                    if (preg_match('/[a-z0-9._-]+\/slices\/(\w+)/', $this->url, $matches)) {
                        if (!empty($matches[1])) {
                            $this->type = 'ProductCatalog/Slice';
                        }
                    }
                } catch (\Exception $e) {}
            }
        }
    }
}