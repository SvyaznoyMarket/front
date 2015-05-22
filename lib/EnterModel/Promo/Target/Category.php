<?php

namespace EnterModel\Promo\Target;

use EnterModel as Model;

class Category extends Model\Promo\Target {
    /** @var string */
    public $type = 'ProductCatalog/Category';
    /** @var string|null */
    public $categoryId;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        parent::__construct($data);
        if (isset($data['id'])) $this->categoryId = (string)$data['id'];
    }
}