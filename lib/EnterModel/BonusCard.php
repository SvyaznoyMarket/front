<?php

namespace EnterModel;

use EnterModel as Model;

class BonusCard {
    const TYPE_MNOGORU = 'mnogoru';

    /** @var string */
    public $id;
    /** @var string */
    public $type;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        switch ($this->id) {
            case '2':
                $this->type = self::TYPE_MNOGORU;
                break;
        }
    }
}
