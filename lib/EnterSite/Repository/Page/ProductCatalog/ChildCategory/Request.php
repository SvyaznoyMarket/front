<?php

namespace EnterSite\Repository\Page\ProductCatalog\ChildCategory;

use Enter\Http;
use EnterSite\Model;
use EnterSite\Repository;

class Request extends Repository\Page\DefaultLayout\Request {
    /** @var \EnterModel\Product\Category */
    public $category;
    /** @var \EnterModel\Product\Catalog\Config */
    public $catalogConfig;
    /** @var \EnterModel\Product\RequestFilter[] */
    public $requestFilters = [];
    /** @var \EnterModel\Product\Filter[] */
    public $filters = [];
    /** @var \EnterModel\Product\Sorting */
    public $sorting;
    /** @var \EnterModel\Product\Sorting[] */
    public $sortings = [];
    /** @var int */
    public $pageNum;
    /** @var int */
    public $limit;
    /** @var int */
    public $count;
    /** @var \EnterModel\Product[] */
    public $products = [];
    /** @var Http\Request */
    public $httpRequest;
}