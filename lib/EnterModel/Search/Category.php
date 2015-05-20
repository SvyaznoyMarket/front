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
        /** @var Model\Product\Category\Media */
        public $media;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            $this->media = new Model\Product\Category\Media();

            if (array_key_exists('id', $data)) $this->id = $data['id'] ? (string)$data['id'] : null;
            if (array_key_exists('token', $data)) $this->token = $data['token'] ? (string)$data['token'] : null;
            if (array_key_exists('name', $data)) $this->name = $data['name'] ? (string)$data['name'] : null;
            if (array_key_exists('link', $data)) $this->link = $data['link'] ? rtrim((string)$data['link'], '/') : null;

            $media = new Model\Media();
            // FIXME
            $media->type = 'image';
            $media->tags = ['main'];
            $media->sources = call_user_func(function($url) {
                $source = new Model\Media\ImageSource();
                $source->url = $url;
                $source->width = 163; // FIXME!!!
                $source->height = 163; // FIXME!!!
                $source->type = 'category_163x163'; // FIXME!!!

                return $source;
            }, $data['image']);
            $this->media->photos[] = $media;
        }
    }
}