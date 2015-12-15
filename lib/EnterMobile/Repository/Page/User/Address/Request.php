<?php
namespace EnterMobile\Repository\Page\User\Address;

use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\User\DefaultPage\Request {
    /** @var \EnterModel\Address[] */
    public $addresses = [];
    /** @var \EnterModel\Region[] */
    public $regionsById = [];
}