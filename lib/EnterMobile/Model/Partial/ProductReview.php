<?php

namespace EnterMobile\Model\Partial;

use EnterMobile\Model\Partial;

class ProductReview {
    /** @var string */
    public $createdAt;
    /** @var string */
    public $author;
    /** @var Partial\Rating\Star[] */
    public $stars = [];
    /** @var string */
    public $extract;
    /** @var string */
    public $pros;
    /** @var string */
    public $cons;
}