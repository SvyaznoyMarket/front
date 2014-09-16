<?php
namespace EnterModel\Cart\Split;

class Error {
    /** @var int */
    public $code = 0;
    /** @var string|null */
    public $message;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->code = (int)$data['code'];
        $this->message = (string)$data['message'];
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'code'    => $this->code,
            'message' => $this->message,
        ];
    }
}
