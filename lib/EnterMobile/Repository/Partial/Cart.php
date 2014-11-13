<?php

namespace EnterMobile\Repository\Partial;

use EnterAggregator\TranslateHelperTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class Cart {
    use TranslateHelperTrait, TemplateHelperTrait;

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
        $cart->credit->isHidden = true;

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