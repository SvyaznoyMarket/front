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
     * @param int $count
     * @param bool $isDisabled
     * @param bool $hasBuyButton
     * @param string|null $buttonId
     * @return Partial\Cart\ProductSpinner
     */
    public function getObject(
        \EnterModel\Product $product,
        $count = 1,
        $isDisabled = false,
        $hasBuyButton = true,
        $buttonId = null
    ) {
        if ($product->relation && (bool)$product->relation->kits) {
            return null;
        }

        $spinner = new Partial\Cart\ProductSpinner();

        $spinner->id = self::getId($product->id, $hasBuyButton);
        $spinner->widgetId = self::getWidgetId($product->id, $hasBuyButton);
        $spinner->value = $count;
        $spinner->isDisabled = $isDisabled;
        $spinner->buttonDataValue = false;
        $spinner->timer = false;
        $spinner->dataValue = $this->helper->json([
            'product' => [
                'id'       => $product->id,
                'name'     => $product->name,
                'token'    => $product->token,
                'price'    => $product->price,
                'url'      => $product->link,
                'quantity' => $count,
            ],
        ]);

        if ($hasBuyButton) {
            $spinner->buttonId = $buttonId ?: Repository\Partial\Cart\ProductButton::getId($product->id);
        } else {
            $spinner->buttonId = $buttonId ?: self::getId($product->id, $hasBuyButton) . '-input';
            $spinner->dataUrl = $this->router->getUrlByRoute(new Routing\User\Cart\Product\Set());
            $spinner->timer = 600;
            $buttonDataValue = ['product' => [
                $product->id => [
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'token'    => $product->token,
                    'price'    => $product->price,
                    'url'      => $product->link,
                    'quantity' => $count,
                ],
            ]];
            $spinner->buttonDataValue = $this->helper->json($buttonDataValue);
        }

        return $spinner;
    }

    /**
     * @param $productId
     * @param bool $hasBuyButton
     * @return string
     */
    public static function getId($productId, $hasBuyButton) {
        return 'id-cart-product-buySpinner' . ($hasBuyButton ? 'WithButton' : '') . '-' . $productId;
    }

    /**
     * @param $productId
     * @param bool $hasBuyButton
     * @return string
     */
    public static function getWidgetId($productId, $hasBuyButton) {
        return self::getId($productId, $hasBuyButton) . '-widget';
    }
}