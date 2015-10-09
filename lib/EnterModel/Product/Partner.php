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
    /** @var string */
    public $offerContentId;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('type', $data)) $this->type = (int)$data['type'];
        if (array_key_exists('id', $data)) $this->ui = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (!empty($data['offer'])) {
            $this->offerUrl = (string)$data['offer'];
            $this->offerContentId = preg_match('/([^\/]+)\/?$/s', $data['offer'], $matches) ? (string)$matches[1] : ''; // TODO после решения CORE-3134 использовать соответствующее поле
        }
    }
}