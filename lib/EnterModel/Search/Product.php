<?php

namespace EnterModel\Search {
    use EnterModel as Model;

    class Product {
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
            if (isset($data['url'])) $this->link = (string)$data['url'];

            if (isset($data['medias'])) {
                $this->media = new Model\MediaList($data['medias']);
            } else {
                $this->media = new Model\MediaList();
            }
        }
    }
}