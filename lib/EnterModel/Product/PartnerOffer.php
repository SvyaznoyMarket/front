<?php

namespace EnterModel\Product;

use EnterModel as Model;

class PartnerOffer {
    /** @var string */
    public $name;
    /** @var int */
    public $type;
    /** @var string */
    public $offerUrl;

    public function __construct(array $data = []) {
        if (isset($data['name'])) $this->name = $data['name'];
        if (isset($data['type'])) $this->type = (int)$data['type'];
        if (isset($data['offer'])) $this->offerUrl = $data['offer'];
    }
}