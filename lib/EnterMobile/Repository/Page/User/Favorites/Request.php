<?php
namespace EnterMobile\Repository\Page\User\Favorites;

use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\User\DefaultPage\Request {
    /** @var array */
    public $favoriteProducts;
}