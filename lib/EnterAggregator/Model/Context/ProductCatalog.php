<?php

namespace EnterAggregator\Model\Context;

use EnterAggregator\Model\Context;

class ProductCatalog extends Context {
    /**
     * Загружать родительскую категорию с ее потомками
     *
     * @var bool
     */
    public $parentCategory = false;
    /**
     * Загружать ветку категории
     *
     * @var bool
     */
    public $branchCategory = false;
    /**
     * Загружать товары только для конечных категорий
     *
     * @var bool
     */
    public $productOnlyForLeafCategory = false;
    /**
     * Загружать остатки товаров по магазинам
     *
     * @var bool
     */
    public $shopState = false;
}