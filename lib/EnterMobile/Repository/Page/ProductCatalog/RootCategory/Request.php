<?php

namespace EnterMobile\Repository\Page\ProductCatalog\RootCategory;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var \EnterModel\Product\Category */
    public $category;
    /** @var \EnterModel\Product\Catalog\Config */
    public $catalogConfig;
}