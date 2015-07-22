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
     * @param string $imageSize
     * @return Partial\ProductCard
     */
    public function getObject(
        \EnterModel\Product $product,
        Partial\Cart\ProductButton $cartButton = null,
        \EnterModel\Product\Category $category = null,
        $imageSize = 'product_160'
    ) {
        $mediaRepository = (new \EnterRepository\Media());
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
            $card->image = $mediaRepository->getSourceObjectByItem($photo, $imageSize)->url;
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

        $rootCategory = $category ? (new \EnterRepository\Product\Category())->getRootObject($category) : null;

        // Не показываем этикетку бренда в списке товаров категории tchibo
        if (
            (!$category || !$category->parent || ($rootCategory && ('tchibo' !== $rootCategory->token)))
            && $product->brand
        ) {
            $card->brand = new \EnterMobile\Model\Partial\ProductCard\Brand();
            $card->brand->id = $product->brand->id;
            $card->brand->name = $product->brand->name;
            $card->brand->token = $product->brand->token;
            $card->brand->imageUrl = $mediaRepository->getSourceObjectByList($product->brand->media->photos, 'product', 'original')->url;
        }

        // состояние товара
        $card->states['isBuyable'] = $product->isBuyable;
        $card->states['isInShopOnly'] = $product->isInShopOnly;
        $card->states['isInShopStockOnly'] = $product->isInShopStockOnly;
        $card->states['isInShopShowroomOnly'] = $product->isInShopShowroomOnly;
        $card->states['isInWarehouse'] = $product->isInWarehouse;
        $card->states['isKitLocked'] = $product->isKitLocked;
        $card->states['isFurnitureItem'] = $product->category && $product->category->isFurniture;

        // шильдики
        $card->labels = [];
        foreach ($product->labels as $label) {
            $viewLabel = new \EnterMobile\Model\Partial\ProductCard\Label();
            $viewLabel->id = $label->id;
            $viewLabel->name = $label->name;
            $viewLabel->imageUrl = $mediaRepository->getSourceObjectByList($label->media->photos, '66x23', 'original')->url;
            $card->labels[] = $viewLabel;
        }

        // значки со склада, в магазинах, на витрине
        if (!$product->isInShopOnly
            && $product->category
            && $product->category->isFurniture
            && $product->isStore
            && !$product->getSlotPartnerOffer()
        ) {
            $card->stateLabel = ['name' => 'Товар со склада', 'cssClassName' => 'availability--instock'];
        } elseif($product->isInShopOnly && $product->isInShopStockOnly) {
            $card->stateLabel = ['name' => 'Только в магазинах', 'cssClassName' => 'availability--on-display'];
        } elseif($product->isInShopShowroomOnly) {
            $card->stateLabel = ['name' => 'Только на витрине', 'cssClassName' => 'availability--on-display'];
        }

        return $card;
    }
}