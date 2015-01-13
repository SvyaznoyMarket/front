<?php

namespace EnterModel\Subscribe;

class Channel {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var bool */
    public $isActive;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('email', $data)) $this->email = (string)$data['email'];
        if (array_key_exists('is_active', $data)) $this->isActive = (bool)$data['is_active'];
    }
}