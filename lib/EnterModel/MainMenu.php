<?php

namespace EnterModel {
    use EnterModel as Model;

    class MainMenu {
        /** @var Model\MainMenu\Element[] */
        public $elements = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (isset($data['items'][0])) {
                foreach ($data['items'] as $elementItem) {
                    $this->elements[] = new Model\MainMenu\Element($elementItem);
                }
            }
        }
    }
}

namespace EnterModel\MainMenu {
    use EnterModel as Model;
    use EnterAggregator\ConfigTrait; // FIXME!!!

    class Element {
        use ConfigTrait;

        /** @var string */
        public $type;
        /** @var string */
        public $id;
        /** @var string */
        public $name;
        /** @var string */
        public $char;
        /** @var string */
        public $image;
        /** @var string */
        public $url;
        /** @var int */
        public $level = 1;
        /** @var Model\MainMenu\Element[] */
        public $children = [];
        /** @var bool */
        public $hasChildren;
        /** @var Model\Media[] */
        public $media = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            $applicationTags = (array)$this->getConfig()->applicationTags;

            if (isset($data['type'])) $this->type = (string)$data['type'];
            if (isset($data['id'])) $this->id = (string)$data['id'];
            if (isset($data['name'])) $this->name = (string)$data['name'];
            if (isset($data['char'])) $this->char = (string)$data['char'];
            if (isset($data['link'])) $this->url = trim((string)$data['link']);
            if (isset($data['medias']) && is_array($data['medias'])) {
                foreach ($data['medias'] as $mediaItem) {
                    $media = new Model\Media($mediaItem);
                    if (isset($media->tags[0]) && !(bool)array_intersect($applicationTags, $media->tags)) continue;

                    $this->media[] = $media;
                }
            }
            if (empty($this->char) && (bool)$this->media) {
                foreach ($this->media as $media) {
                    if ('image' == $media->type) {
                        /** @var Model\Media\ImageSource|null $source */
                        if ($source = reset($media->sources)) {
                            $this->image = $source->url;
                        }
                    }
                }
            }
        }
    }
}
