<?php

namespace EnterMobile\Repository\Page\Search;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var string */
    public $searchPhrase;
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
    /** @var bool */
    public $buyBtnListing;
}