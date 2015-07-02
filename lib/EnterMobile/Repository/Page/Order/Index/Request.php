<?php

namespace EnterMobile\Repository\Page\Order\Index;

use Enter\Http;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultPage\Request {
    /** @var array */
    public $formErrors;
    /** @var array */
    public $userData;
    /** @var string */
    public $shopId;
    /** @var \EnterModel\BonusCard[] */
    public $bonusCardsByType = [];
}