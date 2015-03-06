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
        $card->price = $product->price;
        $card->shownPrice = $product->price ? $this->getPriceHelper()->format($product->price) : null;
        $card->sum = (new Repository\Partial\Cart\ProductSum())->getObject($cartProduct);
        $card->oldPrice = $product->oldPrice;
        $card->shownOldPrice = $product->oldPrice ? $this->getPriceHelper()->format($product->oldPrice) : null;
        if ($photo = reset($product->media->photos)) {
            /** @var \EnterModel\Product\Media\Photo $photo */
            $card->image = (string)(new Routing\Product\Media\GetPhoto($photo->source, $photo->id, 1));
        }
        $card->id = $product->id;
        $card->cartSpinner = $cartSpinner;
        $card->cartDeleteButton = $cartDeleteButton;

        return $card;
    }
}