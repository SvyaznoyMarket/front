<?php

namespace EnterMobile\Repository\Page\ProductCatalog\Slice;

use EnterMobile\Repository;

class Request extends Repository\Page\ProductCatalog\ChildCategory\Request {
    /** @var \EnterModel\Product\Slice */
    public $slice;
}