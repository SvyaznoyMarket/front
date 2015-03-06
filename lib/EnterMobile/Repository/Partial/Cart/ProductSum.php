<?php

namespace EnterMobile\Repository\Partial\Cart;

use EnterAggregator\PriceHelperTrait;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class ProductSum {
    use PriceHelperTrait;

    /**
     * @param \EnterModel\Cart\Product|null $cartProduct
     * @return Partial\Cart\ProductSum|null
     */
    public function getObject(
        \EnterModel\Cart\Product $cartProduct
    ) {
        $productSum = null;

        if ($cartProduct) {
            $productSum = new Partial\Cart\ProductSum();
            $productSum->widgetId = self::getWidgetId($cartProduct->id);
            $productSum->value = $cartProduct->sum;
            $productSum->shownValue = $this->getPriceHelper()->format($cartProduct->sum);
        }

        return $productSum;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getId($productId) {
        return 'id-cart-productSum-' . $productId;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getWidgetId($productId) {
        return self::getId($productId) . '-widget';
    }
}