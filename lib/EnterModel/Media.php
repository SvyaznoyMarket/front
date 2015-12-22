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

        /**
         * @param array $data
         */
        public function fromArray(array $data) {
            if (isset($data['uid'])) $this->uid =  $data['uid'];
            if (isset($data['contentType'])) $this->contentType =  $data['contentType'];
            if (isset($data['type'])) $this->type =  $data['type'];
            if (isset($data['tags'])) $this->tags =  $data['tags'];

            if (isset($data['sources']) && is_array($data['sources'])) {
                if ($this->type === 'image') {
                    $this->sources = array_map(function($item) use($data) {
                        $source = new Model\Media\ImageSource();
                        $source->fromArray($item);
                        return $source;
                    }, $data['sources']);
                } else if ($this->type === 'svg') {
                    $this->sources = array_map(function($item) use($data) {
                        $source = new Model\Media\SvgSource();
                        $source->fromArray($item);
                        return $source;
                    }, $data['sources']);
                }
            }
        }
    }
}