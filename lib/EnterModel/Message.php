<?php

namespace EnterModel;

class Message {
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_ERROR = 'error';

    /** @var string */
    public $type;
    /** @var int */
    public $code;
    /** @var string */
    public $name;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['type'])) $this->type = (string)$data['type'];
        if (isset($data['code'])) $this->code = (int)$data['code'];
        if (isset($data['name'])) $this->name = (string)$data['name'];
    }
}