<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Relation {
    /** @var Model\Product[] */
    public $accessories = [];
    /** @var Model\Product[] */
    public $similar = [];
}