<?php
namespace EnterModel\Cart\Split;

class Error {
    /** @var int */
    public $code = 0;
    /** @var string|null */
    public $message;

    public function __construct($data = []) {
        $this->code = (int)$data['code'];
        $this->message = (string)$data['message'];
    }
}
