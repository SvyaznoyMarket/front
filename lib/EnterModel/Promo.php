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
        /** @var Model\MediaList */
        public $media;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
            if (array_key_exists('name', $data)) $this->name = (string)$data['name'];

            if (isset($data['category'])) {
                $this->target = new Model\Promo\Target\Category($data['category']);
            } else if (isset($data['slice'])) {
                $this->target = new Model\Promo\Target\Slice($data['slice']);
            } else if (isset($data['static_page'])) {
                $this->target = new Model\Promo\Target\Content($data['static_page']);
            }

            $this->media = new Model\MediaList(isset($data['medias']) ? $data['medias'] : []);
        }

        /**
         * @param string $mediaTag
         * @param string $sourceType
         * @return Media\ImageSource|Media\SvgSource|null
         */
        public function getPhotoMediaSource($mediaTag, $sourceType) {
            foreach ($this->media->photos as $media) {
                if (in_array($mediaTag, $media->tags)) {
                    foreach ($media->sources as $source) {
                        if ($source->type === $sourceType) {
                            return $source;
                        }
                    }
                }
            }

            return null;
        }
    }
}