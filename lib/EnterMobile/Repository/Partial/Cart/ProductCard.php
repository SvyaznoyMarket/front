<?php

namespace EnterMobile\Repository\Partial\Cart;

use EnterAggregator\PriceHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Repository;

class ProductCard {
    use PriceHelperTrait;

    /**
     * @param \EnterModel\Cart\Product $cartProduct
     * @param \EnterModel\Product $product
     * @param Partial\Cart\ProductSpinner|null $cartSpinner
     * @param Partial\Cart\ProductDeleteButton|null $cartDeleteButton
     * @return Partial\ProductCard
     */
    public function getObject(
        \EnterModel\Cart\Product $cartProduct,
        \EnterModel\Product $product,
        Partial\Cart\ProductSpinner $cartSpinner = null,
        Partial\Cart\ProductDeleteButton $cartDeleteButton = null
    ) {
        $card = new Partial\Cart\ProductCard();

        $card->name = $product->name;
        $card->url = $product->link;

        if ($product->sender) {
            $card->url .= (false === strpos($card->url, '?') ? '?' : '&') . http_build_query($product->sender);
        }

        $card->price = $product->price;
        $card->shownPrice = $product->price ? $this->getPriceHelper()->format($product->price) : null;
        $card->sum = (new Repository\Partial\Cart\ProductSum())->getObject($cartProduct);
        $card->oldPrice = $product->oldPrice;
        $card->shownOldPrice = $product->oldPrice ? $this->getPriceHelper()->format($product->oldPrice) : null;
        /** @var \EnterModel\Media|null $photo */
        if ($photo = reset($product->media->photos)) {
            $card->image = (new \EnterRepository\Media())->getSourceObjectByItem($photo, 'product_120')->url;
        }
        $card->id = $product->id;
        $card->cartSpinner = $cartSpinner;
        $card->cartDeleteButton = $cartDeleteButton;

        return $card;
    }
}