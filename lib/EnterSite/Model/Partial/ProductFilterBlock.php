<?php

namespace EnterSite\Model\Partial;

use EnterSite\Model\Partial;

class ProductFilterBlock {
    /** @var Partial\ProductFilter[] */
    public $filters = [];
    /** @var Partial\ProductFilter[] */
    public $openedFilters = [];
    /** @var Partial\ProductFilterActionBlock */
    public $actionBlock;
    /** @var string */
    public $dataGa;

    public function __construct() {
        $this->actionBlock = new Partial\ProductFilterActionBlock();
    }
}