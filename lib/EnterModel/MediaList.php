<?php

namespace EnterModel {
    use EnterModel as Model;

    class MediaList {
        /** @var Model\Media[] */
        public $photos = [];

        /**
         * @param mixed $data
         */
        public function __construct($data = []) {
            if (is_array($data)) {
                foreach ($data as $item) {
                    if (!isset($item['sources'][0])) continue;

                    $media = new Model\Media($item);
                    if ('image' === $media->type) {
                        $this->photos[] = $media;
                    }
                }
            }
        }

        /**
         * @param array $data
         */
        public function fromArray(array $data) {
            if (isset($data['photos']) && is_array($data['photos'])) {
                $this->photos = array_map(function($item) {
                    $media = new Model\Media();
                    $media->fromArray($item);
                    return $media;
                }, $data['photos']);
            }
        }
    }
}