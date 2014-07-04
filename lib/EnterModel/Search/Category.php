<?php

namespace EnterModel\Search {
    use EnterModel as Model;

    class Category {
        /** @var string */
        public $id;
        /** @var string */
        public $token;
        /** @var string */
        public $name;
        /** @var string */
        public $link;
        /** @var string */
        public $image;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('id', $data)) $this->id = $data['id'] ? (string)$data['id'] : null;
            if (array_key_exists('token', $data)) $this->token = $data['token'] ? (string)$data['token'] : null;
            if (array_key_exists('name', $data)) $this->name = $data['name'] ? (string)$data['name'] : null;
            if (array_key_exists('link', $data)) $this->link = $data['link'] ? (string)$data['link'] : null;
            if (array_key_exists('image', $data)) $this->image = $data['image'] ? (string)$data['image'] : null;
        }
    }
}