<?php

namespace EnterSite\Repository\Partial\Cart;

use Enter\Routing\Router;
use Enter\Helper;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterSite\Routing;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Partial;

class ProductLink {
    use RouterTrait;

    /** @var Router */
    protected $router;

    public function __construct() {
        $this->router = $this->getRouter();
    }

    /**
     * @param \EnterModel\Product $product
     * @param \EnterModel\Cart\Product|null $cartProduct
     * @return Partial\Cart\ProductLink|null
     */
    public function getObject(
        \EnterModel\Product $product,
        \EnterModel\Cart\Product $cartProduct = null
    ) {
        $link = new Partial\Cart\ProductLink();

        $link->id = self::getId($product->id);
        $link->widgetId = self::getWidgetId($product->id);

        // если товар в корзине
        if ($cartProduct) {
            $link->url = $this->router->getUrlByRoute(new Routing\Cart\Index());
            $link->quantity = $cartProduct->quantity;
        } else {
            return null;
        }

        return $link;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getId($productId) {
        return 'id-cart-product-link-' . $productId;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getWidgetId($productId) {
        return self::getId($productId) . '-widget';
    }
}