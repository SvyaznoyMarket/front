<?php

namespace EnterMobile\Repository\Page\Index;

use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var \EnterModel\Promo[] */
    public $promos;
    /** @var  \EnterModel\Brand[] */
    public $popularBrands = [];
}