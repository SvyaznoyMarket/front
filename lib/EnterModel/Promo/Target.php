<?php

namespace EnterModel\Promo;

use EnterModel as Model;

abstract class Target {
    /** @var string|null */
    public $url;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (isset($data['url'])) $this->url = (string)$data['url'];
    }
}