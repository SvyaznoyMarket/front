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

    class Element {
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
        /** @var string */
        public $style;
        /** @var string */
        public $styleHover;
        /** @var string */
        public $class;
        /** @var string */
        public $classHover;
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
            if (isset($data['type'])) $this->type = (string)$data['type'];
            if (isset($data['id'])) $this->id = (string)$data['id'];
            if (isset($data['name'])) $this->name = (string)$data['name'];
            if (isset($data['char'])) $this->char = (string)$data['char'];
            if (isset($data['link'])) $this->url = trim((string)$data['link']);
            if (isset($data['style'])) $this->style = (string)$data['style'];
            if (isset($data['styleHover'])) $this->styleHover = (string)$data['styleHover'];
            if (isset($data['class'])) $this->class = (string)$data['class'];
            if (isset($data['classHover'])) $this->classHover = (string)$data['classHover'];
            if (isset($data['medias']) && is_array($data['medias'])) {
                foreach ($data['medias'] as $i => $mediaItem) {
                    $this->media[$i] = new Model\Media($mediaItem);
                }
            }
            if (empty($this->char) && (bool)$this->media) {
                foreach ($this->media as $media) {
                    if ('image' == $media->type) {
                        /** @var Model\Media\ImageSource|null $source */
                        $source = reset($media->sources);
                    }
                }
            }
        }
    }
}
