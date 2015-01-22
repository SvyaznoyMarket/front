<?php

namespace EnterMobile\Repository\Page\Product\ReviewList;

use EnterMobile\Model;

class Request {
    /** @var \EnterModel\Product\Review[] */
    public $reviews = [];
    /** @var int */
    public $reviewCount;
    /** @var int */
    public $pageNum;
    /** @var int */
    public $limit;
}