<?php

namespace EnterModel\Product;

class UiPager {
    /** @var array */
    public $uis;
    /** @var int */
    public $count;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('list', $data) && is_array($data['list'])) $this->uis = $data['list'];
        if (array_key_exists('count', $data)) $this->count = (int)$data['count'];
    }
}