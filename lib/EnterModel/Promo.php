<?php

namespace EnterModel {
    use EnterModel as Model;

    class Promo {
        /** @var string */
        public $id;
        /** @var string */
        public $type;
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $urlType;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            $this->media = new Model\Product\Category\Media();

            if (array_key_exists('uid', $data)) $this->id = (string)$data['uid'];
            if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
            if (array_key_exists('url', $data)) $this->url = $data['url'] ? (string)$data['url'] : null;
            if (array_key_exists('url_type', $data)) $this->urlType = $data['url_type'] ? (string)$data['url_type'] : null;
            if (isset($data['medias'][0])) {
                foreach ($data['medias'] as $mediaItem) {
                    if (!isset($mediaItem['sources'][0])) continue;

                    $media = new Model\Media($mediaItem);

                    if ('image' == $media->type) {
                        $this->media->photos[] = new Model\Media($mediaItem);
                    }
                }
            }
        }
    }
}