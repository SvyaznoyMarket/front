<?php

namespace EnterSite\Repository\Page\ProductCard;

use EnterSite\Model;
use EnterSite\Repository;

class Request extends Repository\Page\DefaultLayout\Request {
    /** @var \EnterModel\Product */
    public $product;
    /** @var \EnterModel\Product\Category[] */
    public $accessoryCategories = [];
    /** @var \EnterModel\Product\Review[] */
    public $reviews = [];
}