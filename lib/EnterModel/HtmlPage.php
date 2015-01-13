<?php

namespace EnterModel {
    class HtmlPage {
        /** @var string */
        public $path;
        /** @var string */
        public $title;
        /** @var string */
        public $header;
        /** @var HtmlPage\Meta[] */
        public $metas = [];
        /** @var string[] */
        public $styles = [];

        public function __construct() {

        }
    }
}

namespace EnterModel\HtmlPage {
    class Meta {
        /** @var string */
        public $charset;
        /** @var string */
        public $content;
        /** @var string */
        public $httpEquiv;
        /** @var string */
        public $name;
    }
}