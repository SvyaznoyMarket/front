<?php

namespace EnterMobile\Repository\Page\Index\RecommendedList;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var \EnterModel\Promo[] */
    public $promos = [];
    /** @var array */
    public $popularItems = [];
    /** @var array */
    public $personalItems = [];
    /** @var array */
    public $viewedItems = [];

}