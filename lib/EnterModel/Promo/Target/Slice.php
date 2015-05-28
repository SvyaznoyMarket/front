<?php

namespace EnterModel\Promo\Target;

use EnterModel as Model;

class Slice extends Model\Promo\Target {
    /** @var string */
    public $type = 'ProductCatalog/Slice';
    /** @var string|null */
    public $sliceId;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        parent::__construct($data);
        if (isset($data['token'])) $this->sliceId = (string)$data['token'];
    }
}