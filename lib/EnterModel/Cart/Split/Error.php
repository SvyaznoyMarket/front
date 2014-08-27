<?php
namespace EnterModel\Cart\Split;

class Error {
    /** @var int */
    public $code = 0;
    /** @var string|null */
    public $message;

    public function __construct($data = []) {
        if (isset($data['code'])) {
            $this->code = (int)$data['code'];
        }

        if (isset($data['message'])) {
            $this->message = (string)$data['message'];
        }
    }
}
