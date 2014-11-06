<?php

namespace EnterAggregator\Model\Context\Product;

use EnterAggregator\Model\Context;

class RecommendedList extends Context {
    /**
     * Похожие товары
     *
     * @var bool
     */
    public $similarIdList = false;
    /**
     * Также покупают
     *
     * @var bool
     */
    public $alsoBought = false;
    /**
     * Также смотрят
     *
     * @var bool
     */
    public $alsoViewed = false;
}