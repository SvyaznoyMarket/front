<?php

namespace EnterSite\Repository\Page\Product\RecommendedList;

use EnterSite\Model;

class Request {
    /** @var \EnterModel\Product */
    public $product;
    /** @var \EnterModel\Product[] */
    public $productsById;
    /** @var string[] */
    public $alsoBoughtIdList = [];
    /** @var string[] */
    public $similarIdList = [];
    /** @var string[] */
    public $alsoViewedIdList = [];
}