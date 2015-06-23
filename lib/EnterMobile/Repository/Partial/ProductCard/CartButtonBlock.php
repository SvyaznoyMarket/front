<?php

namespace EnterMobile\Repository\Partial\ProductCard;

use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class CartButtonBlock {

    public function getObject(
        \EnterModel\Product $product,
        \EnterModel\Cart\Product $cartProduct = null,
        array $context = []
    ) {
        if ($product->relation && (bool)$product->relation->kits && !$product->isKitLocked) {
            return null;
        }

        $block = new Model\Partial\ProductCard\CartButtonBlock();
        $block->widgetId = self::getWidgetId($product->id);

        $block->cartLink = (new Repository\Partial\Cart\ProductLink())->getObject($product, $cartProduct) ?: false;
        if (!$cartProduct) {
            $block->cartButton = (new Repository\Partial\Cart\ProductButton())->getObject($product, null, false, false, $context);

            $block->cartQuickButton = (new Repository\Partial\Cart\ProductQuickButton())->getObject($product);
            if ($product->isBuyable && !$product->isInShopOnly) {
                $block->cartSpinner = (new Repository\Partial\Cart\ProductSpinner())->getObject($product);
            }
        }

        return $block;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getId($productId) {
        return 'id-productButtonBlock-' . $productId;
    }

    /**
     * @param $productId
     * @return string
     */
    public static function getWidgetId($productId) {
        return self::getId($productId) . '-widget';
    }
}