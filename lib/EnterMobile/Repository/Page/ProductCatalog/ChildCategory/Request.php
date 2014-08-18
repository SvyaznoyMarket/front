<?php

namespace EnterMobile\Repository\Page\ProductCatalog\ChildCategory;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\ProductCatalog\RootCategory\Request {
    /** @var \EnterModel\Product\Category */
    //public $category;
    /** @var \EnterModel\Product\Catalog\Config */
    //public $catalogConfig;
    /** @var \EnterModel\Product\RequestFilter[] */
    public $baseRequestFilters = [];
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