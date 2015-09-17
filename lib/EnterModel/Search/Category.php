<?php

namespace EnterModel\Search {
    use EnterModel as Model;

    class Category {
        /** @var string */
        public $id;
        /** @var string */
        public $ui;
        /** @var string */
        public $token;
        /** @var string */
        public $name;
        /** @var string */
        public $link;
        /** @var int */
        public $productCount = 0;
        /** @var Model\MediaList */
        public $media;

        /**
         * @param mixed $data
         */
        public function __construct($data = []) {
            if (isset($data['id'])) $this->id = (string)$data['id'];
            if (isset($data['uid'])) $this->ui = (string)$data['uid'];
            if (isset($data['slug'])) $this->token = (string)$data['slug'];
            if (isset($data['name'])) $this->name = (string)$data['name'];
            if (isset($data['url'])) $this->link = rtrim((string)$data['url'], '/');
            if (isset($data['product_count'])) $this->productCount = (int)$data['product_count'];

            if (isset($data['medias'])) {
                $this->media = new Model\MediaList($data['medias']);
            } else {
                $this->media = new Model\MediaList();
            }
        }
    }
}