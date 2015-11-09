<?php

namespace EnterModel {

    use EnterAggregator\ConfigTrait;
    use EnterModel as Model;

    class Media {
        use ConfigTrait; // FIXME: выпилить после 2016-03

        /** @var string */
        public $uid;
        /** @var string */
        public $contentType;
        /** @var string */
        public $type;
        /** @var string[] */
        public $tags = [];
        /** @var Model\Media\ImageSource[]|Model\Media\SvgSource[] */
        public $sources = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            // MAPI-172
            $config = $this->getConfig();
            $clearSource = in_array('app-mobile', $config->applicationTags) && ('1.5' == $config->version); // FIXME

            if (array_key_exists('uid', $data)) $this->uid = (string)$data['uid'];
            if (array_key_exists('content_type', $data)) $this->contentType = (string)$data['content_type'];
            if (array_key_exists('provider', $data)) $this->type = (string)$data['provider'];
            if (array_key_exists('tags', $data)) $this->tags = (array)$data['tags'];
            if (isset($data['sources'][0])) {
                foreach ($data['sources'] as $sourceItem) {
                    if ('image' == $this->type) {
                        if (  // FIXME
                            $clearSource
                            && (
                                (false !== strpos($sourceItem['type'], 'wide'))
                                || (false !== strpos($sourceItem['type'], 'grid'))
                            )
                        ) {
                            continue; // жуть из MAPI-172
                        }

                        $this->sources[] = new Model\Media\ImageSource($sourceItem);
                    } else if ('svg' == $this->type) {
                        $this->sources[] = new Model\Media\SvgSource($sourceItem);
                    }
                }
            }
        }
    }
}

namespace EnterModel\Media {
    class Source {
        /** @var string */
        public $type;
        /** @var string */
        public $url;

        /**
         * @param mixed $data
         */
        public function __construct($data = []) {
            if (isset($data['type'])) $this->type = (string)$data['type'];
            if (isset($data['url'])) $this->url = (string)$data['url'];
        }
    }

    class ImageSource extends Source {
        /** @var int */
        public $width;
        /** @var int */
        public $height;

        /**
         * @param mixed $data
         */
        public function __construct($data = []) {
            parent::__construct($data);

            if (isset($data['width'])) $this->width = (string)$data['width'];
            if (isset($data['height'])) $this->height = (string)$data['height'];
        }
    }

    class SvgSource extends Source {
        /**
         * @param mixed $data
         */
        public function __construct($data = []) {
            parent::__construct($data);
        }
    }
}