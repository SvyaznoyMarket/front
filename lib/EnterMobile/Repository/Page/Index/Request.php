<?php

namespace EnterMobile\Repository\Page\Index;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var \EnterModel\Promo[] */
    public $promos = [];
}