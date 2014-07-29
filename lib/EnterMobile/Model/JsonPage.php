<?php

namespace EnterMobile\Model {
    class JsonPage {
        /** @var JsonPage\Error|null */
        public $error;
        /** @var mixed */
        public $result;

        public function __construct() {}
    }
}

namespace EnterMobile\Model\JsonPage {
    class Error {
        /** @var string */
        public $code;
        /** @var string */
        public $message;
    }
}