<?php

namespace EnterModel;

use EnterModel as Model;

class Seller {
    /** @var string */
    public $id;
    /** @var string */
    public $ui;
    /** @var string */
    public $name;
    /** @var string */
    public $offerUrl;
    /** @var string */
    public $offerText;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('offer', $data)) $this->offerUrl = (string)$data['offer'];
        if (array_key_exists('offer_text', $data)) $this->offerText = (string)$data['offer_text'];
    }
}