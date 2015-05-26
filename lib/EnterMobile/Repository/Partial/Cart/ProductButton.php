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
     * @param bool $allowInShopOnly Позволять отображать кнопку для товаров, которые доступны только в магазине
     * @return Partial\Cart\ProductButton
     */
    public function getObject(
        \EnterModel\Product $product,
        \EnterModel\Cart\Product $cartProduct = null,
        $allowInShopOnly = false,
        $isFull = true
    ) {
        if (!$allowInShopOnly && $product->isInShopOnly) {
            return null;
        }

        // FIXME
        if ($product->relation && (bool)$product->relation->kits && !$product->isKitLocked) {
            return null;
        }

        $button = new Partial\Cart\ProductButton();

        $button->dataUrl = $this->router->getUrlByRoute(new Routing\User\Cart\Product\Set());

        $dataValue = [
            'product' => [
                $product->id => [
                    'id'       => $product->id,
                    'wmId'     => $product->wikimartId,
                    'article'  => $product->article,
                    'name'     => $product->name,
                    'token'    => $product->token,
                    'price'    => $product->price,
                    'url'      => $product->link,
                    'quantity' => $cartProduct ? $cartProduct->quantity : 1,
                ],
            ],
        ];

        if ($product->atListingPage) {
            $ga = [
                ['send', 'event', 'm_add_to_basket', 'listing', $product->article, '{product.sum}'],
            ];
        } elseif($product->atProductPage) {
            $ga = [
                ['send', 'event', 'm_add_to_basket', 'product', $product->article, '{product.sum}'],
            ];
        } else {
            $ga = [
                ['send', 'event', 'm_add_to_basket', $product->name, $product->article, '{product.sum}'],
            ];
        }

        if ($product->ga) {
            $ga[] = [
                'send',
                'event',
                $product->ga['category'],
                $product->ga['events']['addToCart']['action'],
                $product->ga['events']['addToCart']['productName']
            ];
        }

        $button->dataGa = $this->helper->json($ga);

        $button->id = self::getId($product->id);
        $button->widgetId = self::getWidgetId($product->id);
        $button->text = 'Купить';
        $button->isDisabled = false;
        $button->isInShopOnly = false;
        $button->isInCart = false;
        $button->isQuick = false;

        // если товар в корзине
        if ($slotPartnerOffer = $product->getSlotPartnerOffer()) {
            $button->text = $isFull ? 'Как купить?' : 'Отправить заявку';
            $button->isSlot = true;
            $dataValue['product'][$product->id]['partnerName'] = $slotPartnerOffer->partner->name;
            $dataValue['product'][$product->id]['partnerOfferUrl'] = $slotPartnerOffer->partner->offerUrl;
            $dataValue['isFull'] = $isFull;
        } else if ($cartProduct) {
            $button->text = 'В корзине';
            $button->url = '/cart'; // TODO: route
            $button->dataUrl = '';
            $button->isInCart = true;
        } else if ($product->isInShopOnly) {
            $button->isInShopOnly = true;
            $button->text = 'Резерв';
            $button->url = $this->router->getUrlByRoute(new Routing\Order\Quick\Index(), ['product' => ['id' => $product->id, 'quantity' => 1]]);
            $button->isQuick = true;
        } else {
            if (!$product->isBuyable) {
                $button->url = '#';
                $button->text = $product->isInShopShowroomOnly ? 'На витрине' : 'Недоступен';
                $button->isDisabled = true;
            } else if (!$button->url) {
                $button->url = $this->router->getUrlByRoute(new Routing\Cart\SetProduct($product->id));
            }
        }

        if ($product->sender) {
            $button->url .= (false === strpos($button->url, '?') ? '?' : '&') . http_build_query($product->sender);
        }

        $button->dataValue = $this->helper->json($dataValue);

        return $button;
    }

    /**
     * @param \EnterModel\Product[] $products
     * @param \EnterModel\Cart\Product[] $cartProductsById
     * @param string $parentId
     * @param bool $updateState
     * @param string|null $quantitySign + или -
     * @return Partial\Cart\ProductButton
     */
    public function getListObject(
        array $products,
        array $cartProductsById = [],
        $parentId,
        $updateState = true,
        $quantitySign = null
    ) {
        $button = new Partial\Cart\ProductButton();

        $dataValue = [
            'product' => [],
        ];
        foreach ($products as $product) {
            $cartProduct = isset($cartProductsById[$product->id]) ? $cartProductsById[$product->id] : null;

            $dataValue['product'][$product->id] = [
                'id'           => $product->id,
                'article'      => $product->article,
                'name'         => $product->name,
                'token'        => $product->token,
                'price'        => $product->price,
                'url'          => $product->link,
                'quantity'     => $cartProduct ? $cartProduct->quantity : 1,
                'parentId'     => $parentId,
                'quantitySign' => $quantitySign,
            ];
        }

        $button->dataUrl = $this->router->getUrlByRoute(new Routing\User\Cart\Product\Set());
        $button->dataValue = $this->helper->json($dataValue);

        $dataGa = [];
        foreach ($products as $product) {
            $dataGa[] = ['send', 'event', 'm_add_to_basket', $product->name, $product->article, '{product.sum}'];
        }
        $button->dataGa = $this->helper->json($dataGa);

        $button->id = self::getId($parentId, $updateState);
        $button->widgetId = self::getWidgetId($parentId, $updateState);
        $button->text = 'Купить';
        $button->isDisabled = false;
        $button->isInShopOnly = false;
        $button->isInCart = false;
        $button->isQuick = false;

        return $button;
    }

    /**
     * @param string $productId
     * @param bool $updateState
     * @return string
     */
    public static function getId($productId, $updateState = true) {
        return 'id-cart-product-buyButton-' . $productId . ($updateState ? '' : '-withoutUpdate');
    }

    /**
     * @param string $productId
     * @param bool $updateState
     * @return string
     */
    public static function getWidgetId($productId, $updateState = true) {
        return self::getId($productId, $updateState) . '-widget';
    }
}