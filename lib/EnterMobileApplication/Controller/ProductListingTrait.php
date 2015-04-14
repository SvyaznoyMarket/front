<?php

namespace EnterMobileApplication\Controller;

use EnterModel as Model;

trait ProductListingTrait {
    /**
     * @param Model\Product[] $products
     * @return array
     */
    private function getProductList(
        array $products
    ) {
        $result = [];

        foreach ($products as $product) {
            $result[] = [
                'id'                   => $product->id,
                'article'              => $product->article,
                'webName'              => $product->webName,
                'namePrefix'           => $product->namePrefix,
                'name'                 => $product->name,
                'isBuyable'            => $product->isBuyable,
                'isInShopOnly'         => $product->isInShopOnly,
                'isInShopStockOnly'    => $product->isInShopStockOnly,
                'isInShopShowroomOnly' => $product->isInShopShowroomOnly,
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
                        'image' => $label->image,
                    ];
                }, $product->labels),
                'media'                => $product->media,
                'rating'               => $product->rating ? [
                    'score'       => $product->rating->score,
                    'starScore'   => $product->rating->starScore,
                    'reviewCount' => $product->rating->reviewCount,
                ] : null,
                'favorite'        => isset($product->favorite) ? $product->favorite : null,
                'partnerOffers'   => $product->partnerOffers,
                'wikimartId'      => $product->wikimartId
            ];
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