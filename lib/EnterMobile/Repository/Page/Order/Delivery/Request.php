<?php

namespace EnterMobile\Repository\Page\Order\Delivery;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var array */
    public $formErrors;
    /** @var \EnterModel\Cart\Split */
    public $split;
    /** @var string */
    public $shopId;
}