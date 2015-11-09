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

            // Not good hotfix
            usort($this->photos, function (Model\Media $a, Model\Media $b) {
                return count($a->sources) < count($b->sources);
            });
        }
    }
}