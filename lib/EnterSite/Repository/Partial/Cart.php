<?php

namespace EnterSite\Repository\Partial;

use EnterSite\TranslateHelperTrait;
use EnterSite\Routing;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Partial;

class Cart {
    use TranslateHelperTrait;

    /**
     * @param \EnterModel\Cart $cartModel
     * @param \EnterModel\Product[] $productModels
     * @return Partial\Cart
     */
    public function getObject(
        \EnterModel\Cart $cartModel,
        $productModels = []
    ) {
        $cart = new Partial\Cart();
        $cart->widgetId = self::getWidgetId();
        $cart->sum = $cartModel->sum;
        $cart->shownSum = number_format((float)$cartModel->sum, 0, ',', ' ');
        $cart->quantity = count($cartModel);
        $cart->shownQuantity = $cart->quantity . ' ' . $this->getTranslateHelper()->numberChoice($cart->quantity, ['товар', 'товара', 'товаров']);

        $cart->credit = (new Repository\Partial\DirectCredit())->getObject($productModels, $cartModel);

        return $cart;
    }

    /**
     * @return string
     */
    public static function getId() {
        return 'id-cart';
    }

    /**
     * @return string
     */
    public static function getWidgetId() {
        return self::getId() . '-widget';
    }
}