<?php

namespace EnterMobile\Model\Search {
    use EnterModel as Model;

    class Category {
        public $name;
        /** @var string */
        public $link;
        /** @var string */
        public $image;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('name', $data)) $this->name = $data['name'] ? (string)$data['name'] : null;
            if (array_key_exists('link', $data)) $this->link = $data['link'] ? rtrim((string)$data['link'], '/') : null;
            if (array_key_exists('image', $data)) $this->image = $data['image'];
        }
    }
}