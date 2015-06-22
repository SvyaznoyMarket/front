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
        /** @var Model\MediaList */
        public $media;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('id', $data)) $this->id = $data['id'] ? (string)$data['id'] : null;
            if (array_key_exists('token', $data)) $this->token = $data['token'] ? (string)$data['token'] : null;
            if (array_key_exists('name', $data)) $this->name = $data['name'] ? (string)$data['name'] : null;
            if (array_key_exists('link', $data)) $this->link = $data['link'] ? rtrim((string)$data['link'], '/') : null;

            $this->media = new Model\MediaList([[
                'provider' => 'image',
                'tags' => ['main'],
                'sources' => [
                    [
                        'type' => 'category_163x163',
                        'url' => $data['image'],
                        'width' => 163,
                        'height' => 163,
                    ],
                ],
            ]]);
        }
    }
}