<?php

namespace EnterModel {
    use EnterModel as Model;

    class MainMenu {
        /** @var Model\MainMenu\Element[] */
        public $elements = [];
        /** @var array */
        public $serviceElements = [];
        /** @var array */
        public $contentElements = [];

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
