<?php

namespace EnterMobile\Repository\Partial;

use EnterAggregator\RouterTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class Cart {
    use RouterTrait, TranslateHelperTrait, TemplateHelperTrait, PriceHelperTrait;

    /**
     * @param \EnterModel\Cart $cartModel
     * @param \EnterModel\Product[] $productModels
     * @param \EnterModel\Region|null $regionModel
     * @return Partial\Cart
     */
    public function getObject(
        \EnterModel\Cart $cartModel,
        $productModels = [],
        $regionModel = null
    ) {
        $cart = new Partial\Cart();
        $cart->widgetId = self::getWidgetId();
        $cart->sum = $cartModel->sum;
        $cart->shownSum = $this->getPriceHelper()->format($cartModel->sum);
        $cart->quantity = count($cartModel);
        $cart->shownQuantity = $cart->quantity . ' ' . $this->getTranslateHelper()->numberChoice($cart->quantity, ['товар', 'товара', 'товаров']);
        $cart->orderRemainSum = $regionModel ? (new \EnterRepository\Order())->getRemainSum($cart->sum, $regionModel) : null;

        $cart->orderUrl = $this->getRouter()->getUrlByRoute(new Routing\Order\Index());
        $cart->orderDataGa = $this->getTemplateHelper()->json(['m_checkout' => ['send', 'event', 'm_checkout', 'cart']]);

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