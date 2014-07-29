<?php

namespace EnterMobile\Repository\Page\ProductCard;

use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultLayout\Request {
    /** @var \EnterModel\Product */
    public $product;
    /** @var \EnterModel\Product\Category[] */
    public $accessoryCategories = [];
}