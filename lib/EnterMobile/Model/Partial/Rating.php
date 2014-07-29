<?php

namespace EnterMobile\Model\Partial;

use EnterMobile\Model\Partial;

class Rating {
    /** @var int */
    public $reviewCount;
    /** @var Partial\Rating\Star[] */
    public $stars = [];
}