<?php

namespace EnterMobile\Repository\Page\Product\ListByFilter;

use EnterMobile\Model;

class Request {
    /** @var \EnterModel\Product\Filter[] */
    public $filters = [];
    /** @var \EnterModel\Product\RequestFilter[] */
    public $requestFilters = [];
    /** @var \EnterModel\Product\Sorting */
    public $sorting;
    /** @var \EnterModel\Product\Sorting[] */
    public $sortings = [];
    /** @var int */
    public $pageNum;
    /** @var int */
    public $limit;
    /** @var \EnterModel\Product[] */
    public $products = [];
    /** @var int */
    public $count;
    /** @var \EnterModel\Product\Category|null */
    public $category;
    /** @var bool */
    public $buyBtnListing = false;
}