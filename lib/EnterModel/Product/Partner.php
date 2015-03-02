<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Partner {
    const TYPE_SLOT = 2;

    /** @var int */
    public $type;
    /** @var string */
    public $ui;
    /** @var string */
    public $name;
    /** @var string */
    public $offerUrl;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('type', $data)) $this->type = (int)$data['type'];
        if (array_key_exists('id', $data)) $this->ui = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('offer', $data)) $this->offerUrl = (string)$data['offer'];
    }
}