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

class ProductButton {
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
     * @return Partial\Cart\ProductButton
     */
    public function getObject(
        \EnterModel\Product $product,
        \EnterModel\Cart\Product $cartProduct = null
    ) {
        if ($product->isInShopOnly) {
            return null;
        }

        // FIXME
        if ($product->relation && (bool)$product->relation->kits) {
            return null;
        }

        $button = new Partial\Cart\ProductButton();

        $button->dataUrl = $this->router->getUrlByRoute(new Routing\User\Cart\Product\Set());

        $dataValue = ['product' => [
            $product->id => [
                'id'       => $product->id,
                'name'     => $product->name,
                'token'    => $product->token,
                'price'    => $product->price,
                'url'      => $product->link,
                'quantity' => $cartProduct ? $cartProduct->quantity : 1,
            ],
        ]];
        $button->dataValue = $this->helper->json($dataValue);

        // ga
        $button->dataGa = $this->helper->json([
            'm_add_to_basket' => ['send', 'event', 'm_add_to_basket', $product->name, $product->article, '{product.sum}'],
        ]);

        $button->id = self::getId($product->id);
        $button->widgetId = self::getWidgetId($product->id);
        $button->text = 'Купить';
        $button->isDisabled = false;
        $button->isInShopOnly = false;
        $button->isInCart = false;
        $button->isQuick = false;

        // если товар в корзине
        if ($cartProduct) {
            $button->text = 'В корзине';
            $button->url = '/cart'; // TODO: route
            $button->dataUrl = '';
            $button->isInCart = true;
        } else {
            if (!$product->isBuyable) {
                $button->url = '#';
                $button->text = $product->isInShopShowroomOnly ? 'На витрине' : 'Недоступен';
                $button->isDisabled = true;
            } else if (!$button->url) {
                $button->url = $this->router->getUrlByRoute(new Routing\Cart\SetProduct($product->id));
            }
        }

        return $button;
    }

    /**
     * @param \EnterModel\Product[] $products
     * @param \EnterModel\Cart\Product[] $cartProductsById
     * @param string $id
     * @return Partial\Cart\ProductButton
     */
    public function getListObject(
        array $products,
        array $cartProductsById = [],
        $id
    ) {
        $button = new Partial\Cart\ProductButton();

        $dataValue = [
            'product' => [],
        ];
        foreach ($products as $product) {
            $cartProduct = isset($cartProductsById[$product->id]) ? $cartProductsById[$product->id] : null;

            $dataValue['product'][$product->id] = [
                'id'       => $product->id,
                'name'     => $product->name,
                'token'    => $product->token,
                'price'    => $product->price,
                'url'      => $product->link,
                'quantity' => $cartProduct ? $cartProduct->quantity : 1,
            ];
        }

        $button->dataUrl = $this->router->getUrlByRoute(new Routing\User\Cart\Product\Set());
        $button->dataValue = $this->helper->json($dataValue);

        // ga
        $dataGa = [];
        foreach ($products as $product) {
            $dataGa[] = [
                ['send', 'event', 'm_add_to_basket', $product->name, $product->article, '{product.sum}'],
            ];
        }
        $button->dataGa = $this->helper->json($dataGa);

        $button->id = self::getId($id);
        $button->widgetId = self::getWidgetId($id);
        $button->text = 'Купить';
        $button->isDisabled = false;
        $button->isInShopOnly = false;
        $button->isInCart = false;
        $button->isQuick = false;

        return $button;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getId($productId) {
        return 'id-cart-product-buyButton-' . $productId;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getWidgetId($productId) {
        return self::getId($productId) . '-widget';
    }
}