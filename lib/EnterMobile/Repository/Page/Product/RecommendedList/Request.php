<?php

namespace EnterMobile\Repository\Page\Product\RecommendedList;

use EnterMobile\Model;

class Request {
    /** @var \EnterModel\Product */
    public $product;
    /** @var \EnterModel\Product[] */
    public $recommendedProductsById;
    /** @var string[] */
    public $alsoBoughtIdList = [];
    /** @var string[] */
    public $similarIdList = [];
    /** @var string[] */
    public $alsoViewedIdList = [];
}