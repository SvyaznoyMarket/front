<?php

namespace EnterModel {
    use EnterModel as Model;

    class Media {
        /** @var string */
        public $uid;
        /** @var string */
        public $contentType;
        /** @var string */
        public $type;
        /** @var string[] */
        public $tags = [];
        /** @var Model\Media\ImageSource[] */
        public $sources = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('uid', $data)) $this->uid = (string)$data['uid'];
            if (array_key_exists('content_type', $data)) $this->contentType = (string)$data['content_type'];
            if (array_key_exists('provider', $data)) $this->type = (string)$data['provider'];
            if (array_key_exists('tags', $data)) $this->tags = (array)$data['tags'];
            if (isset($data['sources'][0])) {
                foreach ($data['sources'] as $sourceItem) {
                    if ('image' == $this->type) {
                        $this->sources[] = new Model\Media\ImageSource($sourceItem);
                    }
                }
            }
        }
    }
}

namespace EnterModel\Media {
    abstract class Source {
        /** @var string */
        public $type;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('type', $data)) $this->type = (string)$data['type'];
        }
    }

    class ImageSource extends Source {
        /** @var string */
        public $url;
        /** @var int */
        public $width;
        /** @var int */
        public $height;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            parent::__construct($data);

            if (array_key_exists('url', $data)) $this->url = (string)$data['url'];
            if (array_key_exists('width', $data)) $this->width = (string)$data['width'];
            if (array_key_exists('height', $data)) $this->height = (string)$data['height'];
        }
    }
}