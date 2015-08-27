<?php

namespace EnterMobileApplication\Repository;

use Enter\Http;
use EnterModel as Model;

class Product {
    /**
     * Сохраняем id просмотренных товаров в сессии
     * @param string $productId
     * @param \Enter\Http\Session $session
     */
    public function setViewedProductIdToSession($productId, \Enter\Http\Session $session) {
        $viewedProductIds = array_unique(explode(' ', trim($session->get('viewedProductIds'))));
        $viewedProductIds = array_slice($viewedProductIds, -20);
        if (!in_array($productId, $viewedProductIds)) {
            $viewedProductIds = array_slice($viewedProductIds, -19);
            $viewedProductIds[] = $productId;
        }
        $session->set('viewedProductIds', implode(' ', $viewedProductIds));
    }

    public function getMedia(\EnterModel\Product $product) {
        return [
            'photos' => array_filter(array_map(function(\EnterModel\Media $photo) {
                if (!array_intersect(['main', 'additional'], $photo->tags)) {
                    return null;
                }

                return $photo;
            }, $product->media->photos)),
        ];
    }
}