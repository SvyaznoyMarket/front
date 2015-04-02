<?php

namespace EnterMobile\Repository\Partial;

use EnterAggregator\PriceHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Repository;

class ProductCard {
    use PriceHelperTrait;

    /** @var Repository\Partial\Rating */
    private $ratingRepository;

    public function __construct() {
        $this->ratingRepository = new Repository\Partial\Rating();
    }

    /**
     * @param \EnterModel\Product $product
     * @param Partial\Cart\ProductButton|null $cartButton
     * @param \EnterModel\Product\Category $category
     * @return Partial\ProductCard
     */
    public function getObject(
        \EnterModel\Product $product,
        Partial\Cart\ProductButton $cartButton = null,
        \EnterModel\Product\Category $category = null
    ) {
        $card = new Partial\ProductCard();

        $card->name = $product->name;

        if ($product->sender) {
            $card->url = $product->link . '?' . http_build_query($product->sender);
        } else {
            $card->url = $product->link;
        }

        $card->price = $product->price;
        $card->shownPrice = $product->price ? $this->getPriceHelper()->format($product->price) : null;
        $card->oldPrice = $product->oldPrice;
        $card->shownOldPrice = $product->oldPrice ? $this->getPriceHelper()->format($product->oldPrice) : null;
        /** @var \EnterModel\Media|null $photo */
        if ($photo = reset($product->media->photos)) {
            $card->image = (string)(new Routing\Product\Media\GetPhoto($photo, 'product_160'));
        }
        $card->id = $product->id;
        $card->categoryId = $product->category ? $product->category->id : null;
        $card->cartButton = $cartButton;

        // рейтинг товара
        if ($product->rating) {
            $rating = new Partial\Rating();
            $rating->reviewCount = $product->rating->reviewCount;
            $rating->stars = $this->ratingRepository->getStarList($product->rating->starScore);

            $card->rating = $rating;
        }

        // Не показываем этикетку бренда в списке товаров категории tchibo
        if (!$category || !$category->ascendants || 'tchibo' !== $category->ascendants[0]->token) {
            $card->brand = $product->brand;
        }

        // шильдики
        foreach ($product->labels as $label) {
            $label->imageUrl = (string)(new Routing\Product\Label\Get($label->id, $label->image, 0)); // FIXME
        }
        $card->labels = $product->labels;

        return $card;
    }
}