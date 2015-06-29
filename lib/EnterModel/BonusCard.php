<?php

namespace EnterModel;

use EnterModel as Model;

class BonusCard {
    /** @var string */
    public $type;
    /** @var string */
    public $number;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('type', $data)) $this->type = (string)$data['type'];
        if (array_key_exists('number', $data)) $this->number = (string)$data['number'];
    }
}
