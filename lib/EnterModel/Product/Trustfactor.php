<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Trustfactor {
    /** @var string */
    public $ui;
    /** @var string */
    public $name;
    /** @var string */
    public $type;
    /** @var Model\Media|null */
    public $media;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('type', $data)) $this->type = (string)$data['type'];
        if (isset($data['media']['uid'])) $this->media = new Model\Media($data['media']);
    }
}