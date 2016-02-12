<?php

namespace EnterMobileApplication\Controller;

use EnterModel as Model;

trait ProductListingTrait {
    /**
     * @param Model\Product[] $products
     * @param bool $excludeProductsWithoutMedia
     * @param bool $returnKitQuantity
     * @return array
     */
    private function getProductList(
        array $products,
        $excludeProductsWithoutMedia = false,
        $returnKitQuantity = false
    ) {
        $result = [];

        $helper = new \Enter\Helper\Template();
        $productRepository = new \EnterMobileApplication\Repository\Product();

        foreach ($products as $product) {
            if ($excludeProductsWithoutMedia) {
                $hasMedia = false;
                foreach ($product->media as $media) {
                    if ($media) {
                        $hasMedia = true;
                        break;
                    }
                }

                if (!$hasMedia) {
                    continue;
                }
            }

            $resultItem = [
                'id'                   => $product->id,
                'ui'                   => $product->ui,
                'article'              => $product->article,
                'webName'              => $helper->unescape($product->webName),
                'namePrefix'           => $helper->unescape($product->namePrefix),
                'name'                 => $helper->unescape($product->name),
                'isBuyable'            => $product->isBuyable,
                'isInShopOnly'         => $product->isInShopOnly,
                'isInShopStockOnly'    => $product->isInShopStockOnly,
                'isInShopShowroomOnly' => $product->isInShopShowroomOnly,
                'isInWarehouse'        => $product->isInWarehouse,
                'isKit'                => (bool)$product->kit,
                'isKitLocked'          => (bool)$product->isKitLocked,
                'brand'                => $product->brand ? [
                    'id'   => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'price'                => $product->price,
                'oldPrice'             => $product->oldPrice,
                'labels'               => array_map(function(Model\Product\Label $label) {
                    return [
                        'id'    => $label->id,
                        'name'  => $label->name,
                        'media' => $label->media,
                    ];
                }, $product->labels),
                'media'           => $productRepository->getMedia($product),
                'rating'          => $product->rating ? [
                    'score'       => $product->rating->score,
                    'starScore'   => $product->rating->starScore,
                    'reviewCount' => $product->rating->reviewCount,
                ] : null,
                'favorite'        => isset($product->favorite) ? $product->favorite : null,
                'partnerOffers'   => $productRepository->getPartnerOffers($product),
                'storeLabel'      => $product->storeLabel,
            ];
            
            if ($returnKitQuantity) {
                $resultItem['quantity'] = (int)$product->kitCount;
            }
            
            $result[] = $resultItem;
        }

        return $result;
    }

    /**
     * @param Model\Product\Filter[] $filters
     * @return array
     */
    private function getFilterList(
        array $filters
    ) {
        $result = [];

        foreach ($filters as $filter) {
            $result[] = [
                'name'       => $filter->name,
                'token'      => $filter->token,
                'isSlider'   => in_array($filter->typeId, [3, 6]),
                'isMultiple' => $filter->isMultiple,
                'min'        => $filter->min,
                'max'        => $filter->max,
                'unit'       => $filter->unit,
                'isSelected' => isset($filter->isSelected) ? $filter->isSelected : false,
                'value'      => isset($filter->value) ? $filter->value : null,
                'option'     => array_map(function(Model\Product\Filter\Option $option) {
                    return [
                        'id'       => $option->id,
                        'token'    => $option->token,
                        'name'     => $option->name,
                        'quantity' => $option->quantity,
                        //'image'    => $option->image,
                    ];
                }, $filter->option),
            ];
        }

        return $result;
    }

    /**
     * @param Model\Product\Sorting[] $sortings
     * @return array
     */
    private function getSortingList(
        array $sortings
    ) {
        $result = [];

        foreach ($sortings as $sorting) {
            $result[] = [
                'token'     => $sorting->token,
                'name'      => $sorting->name,
                'direction' => $sorting->direction,
            ];
        }

        return $result;
    }
}