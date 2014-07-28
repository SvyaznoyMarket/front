<?php

namespace EnterMobile\Model\Partial;
use EnterMobile\Model\Partial;

class ProductBlock {
    /** @var Partial\ProductCard[] */
    public $products = [];
    /** @var int */
    public $limit;
    /** @var string */
    public $url;
    /** @var string */
    public $dataValue;
    /** @var string */
    public $dataReset;
    /** @var Partial\Link|null */
    public $moreLink;
}