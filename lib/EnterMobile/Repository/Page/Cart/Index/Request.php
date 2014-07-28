<?php

namespace EnterMobile\Repository\Page\Cart\Index;

use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\DefaultLayout\Request {
    /** @var \EnterModel\Cart */
    public $cart;
    /** @var \EnterModel\Product[] */
    public $productsById = [];
    /** @var \EnterModel\Cart\Product[] */
    public $cartProducts = [];
}