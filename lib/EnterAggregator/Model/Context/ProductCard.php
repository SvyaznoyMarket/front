<?php

namespace EnterAggregator\Model\Context;

use EnterAggregator\Model\Context;

class ProductCard extends Context {
    /**
     * Загружать отзывы
     *
     * @var bool
     */
    public $review = false;
    /**
     * Загружать доставку
     *
     * @var bool
     */
    public $delivery = true;
    /**
     * Проверять товары в избранном?
     *
     * @var bool
     */
    public $favourite = false;
}