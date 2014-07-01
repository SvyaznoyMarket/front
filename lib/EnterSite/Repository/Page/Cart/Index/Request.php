<?php

namespace EnterSite\Repository\Page\Cart\Index;

use EnterSite\Model;
use EnterSite\Repository;

class Request extends Repository\Page\DefaultLayout\Request {
    /** @var \EnterModel\Cart */
    public $cart;
    /** @var \EnterModel\Product[] */
    public $productsById = [];
    /** @var \EnterModel\Cart\Product[] */
    public $cartProducts = [];
}