<?php

namespace EnterTerminal\Model\ControllerResponse\Cart;

use EnterModel as Model;

class Split {
    /** @var array */
    public $errors = [];
    /** @var array */
    public $split;
    /** @var Model\Region|null */
    public $region;
    /** @var array */
    public $pointFilters;
}