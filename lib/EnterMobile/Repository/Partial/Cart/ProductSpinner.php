<?php

namespace EnterMobile\Repository\Partial\Cart;

use Enter\Routing\Router;
use Enter\Helper;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Repository;
use EnterMobile\Routing;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class ProductSpinner {
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
     * @param \EnterModel\Cart\Product|null $cartProduct
     * @param bool $hasBuyButton
     * @param string|null $buttonId
     * @param bool $updateState
     * @param string|null $checkUrl
     * @return Partial\Cart\ProductSpinner
     */
    public function getObject(
        \EnterModel\Product $product,
        \EnterModel\Cart\Product $cartProduct = null,
        $hasBuyButton = true,
        $buttonId = null,
        $updateState = true,
        $checkUrl = null
    ) {
        if ($product->relation && (bool)$product->relation->kits) {
            return null;
        }

        if (!$cartProduct) {
            $cartProduct = new \EnterModel\Cart\Product(['quantity' => 1]);
        }

        $spinner = new Partial\Cart\ProductSpinner();

        $spinner->id = self::getId($product->id, $updateState);
        $spinner->widgetId = self::getWidgetId($product->id, $updateState);
        $spinner->value = $cartProduct->quantity;
        $spinner->buttonDataValue = false;
        $spinner->timer = 600;
        $spinner->dataValue = $this->helper->json([
            'product' => [
                'id'          => $product->id,
                'name'        => $product->name,
                'token'       => $product->token,
                'price'       => $product->price,
                'url'         => $product->link,
                'quantity'    => $cartProduct->quantity,
                'minQuantity' => $updateState ? 1 : 0,
            ],
            'checkUrl' => $checkUrl,
        ]);

        if ($hasBuyButton) {
            $spinner->buttonId = $buttonId ?: Repository\Partial\Cart\ProductButton::getId($product->id, $updateState);
        } else {
            $spinner->buttonId = $buttonId ?: self::getId($product->id, false) . '-input';
            $spinner->dataUrl = $this->router->getUrlByRoute(new Routing\User\Cart\Product\Set());
            $buttonDataValue = ['product' => [
                $product->id => [
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'token'    => $product->token,
                    'price'    => $product->price,
                    'url'      => $product->link,
                    'quantity' => $cartProduct->quantity,
                    'parentId' => $cartProduct->parentId
                ],
            ]];
            $spinner->buttonDataValue = $this->helper->json($buttonDataValue);
        }

        return $spinner;
    }

    /**
     * @param $productId
     * @param bool $updateState
     * @return string
     */
    public static function getId($productId, $updateState) {
        return 'id-cart-product-buySpinner' . '-' . $productId . ($updateState ? '' : '-withoutUpdate');
    }

    /**
     * @param $productId
     * @param bool $updateState
     * @return string
     */
    public static function getWidgetId($productId, $updateState = true) {
        return self::getId($productId, $updateState) . '-widget';
    }
}