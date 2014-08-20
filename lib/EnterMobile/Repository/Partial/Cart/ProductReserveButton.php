<?php

namespace EnterMobile\Repository\Partial\Cart;

use Enter\Routing\Router;
use Enter\Helper;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class ProductReserveButton {
    use RouterTrait;
    use TemplateHelperTrait;

    /** @var Router */
    protected $router;
    /** @var Helper\Template */
    protected $helper;

    public function __construct() {
        $this->router = $this->getRouter();
        $this->helper = $this->getTemplateHelper();
    }

    /**
     * @param \EnterModel\Product $product
     * @param \EnterModel\Product\ShopState|null $shopState
     * @return Partial\Cart\ProductButton
     */
    public function getObject(
        \EnterModel\Product $product,
        \EnterModel\Product\ShopState $shopState = null
    ) {
        $button = new Partial\Cart\ProductButton();

        $button->url = $this->router->getUrlByRoute(new Routing\Order\Quick\Index(), [
            'product' => ['id' => $product->id, 'quantity' => 1],
            'shopId'  => $shopState->shop ? $shopState->shop->id : null,
        ]);
        $button->dataUrl = $this->router->getUrlByRoute(new Routing\User\Cart\Product\Set());

        $dataValue = ['product' => [
            $product->id => [
                'id'       => $product->id,
                'name'     => $product->name,
                'token'    => $product->token,
                'price'    => $product->price,
                'url'      => $product->link,
                'quantity' => $shopState ? $shopState->quantity : 1,
            ],
        ]];
        $button->dataValue = $this->helper->json($dataValue);

        // ga
        $button->dataGa = $this->helper->json([
            'm_add_to_basket' => ['send', 'event', 'm_1_click_order', $product->name, $product->article, '{product.sum}'],
        ]);

        $button->id = self::getId($product->id);
        $button->widgetId = self::getWidgetId($product->id);
        $button->text = 'Резерв';
        $button->isDisabled = false;
        $button->isInShopOnly = true;
        $button->isInCart = false;
        $button->isQuick = true;

        if ($shopState->isInShowroomOnly) {
            $button->url = '#';
            $button->text = 'На витрине';
            $button->isDisabled = true;
        }

        if (!$product->isBuyable) {

        } else if (!$button->url) {
            $button->url = $this->router->getUrlByRoute(new Routing\Cart\SetProduct($product->id));
        }

        return $button;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getId($productId) {
        return 'id-cart-product-reserveButton-' . $productId;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getWidgetId($productId) {
        return self::getId($productId) . '-widget';
    }
}