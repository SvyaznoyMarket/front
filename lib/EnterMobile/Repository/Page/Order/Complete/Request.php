<?php

namespace EnterMobile\Repository\Page\Order\Complete;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var \EnterModel\Order[] */
    public $orders = [];
}