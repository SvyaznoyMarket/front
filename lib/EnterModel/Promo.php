<?php

namespace EnterModel {
    use EnterModel as Model;

    class Promo {
        /** @var string */
        public $ui;
        /** @var string */
        public $name;
        /** @var Model\Promo\Target\Category|Model\Promo\Target\Content|Model\Promo\Target\Slice|null */
        public $target;
        /** @var Model\Product\Category\Media */
        public $media;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            $this->media = new Model\Product\Category\Media();

            if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
            if (array_key_exists('name', $data)) $this->name = (string)$data['name'];

            if (isset($data['category'])) {
                $this->target = new Model\Promo\Target\Category($data['category']);
            } else if (isset($data['slice'])) {
                $this->target = new Model\Promo\Target\Slice($data['slice']);
            } else if (isset($data['static_page'])) {
                $this->target = new Model\Promo\Target\Content($data['static_page']);
            }

            if (isset($data['medias'][0])) {
                foreach ($data['medias'] as $mediaItem) {
                    if (!isset($mediaItem['sources'][0])) continue;

                    $media = new Model\Media($mediaItem);

                    if ('image' === $media->type) {
                        $this->media->photos[] = $media;
                    }
                }
            }
        }
    }
}