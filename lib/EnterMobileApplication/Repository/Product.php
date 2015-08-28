<?php

namespace EnterMobileApplication\Repository;

use Enter\Http;
use EnterModel as Model;

class Product extends \EnterRepository\Product {
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

    /**
     * @param Model\Product $product
     * @return array
     */
    public function getMedia(\EnterModel\Product $product) {
        return [
            'photos' => array_values(array_filter(array_map(function(\EnterModel\Media $photo) {
                if (!array_intersect(['main', 'additional'], $photo->tags)) {
                    return null;
                }

                return $photo;
            }, $product->media->photos))),
        ];
    }

    /**
     * @param Model\Product $product
     * @return array
     */
    public function getPartnerOffers(\EnterModel\Product $product) {
        return array_map(function(\EnterModel\Product\PartnerOffer $partnerOffer) {
            return [
                'partner' => [
                    'type' => $partnerOffer->partner->type,
                    'ui' => $partnerOffer->partner->ui,
                    'name' => $partnerOffer->partner->name,
                    'offerUrl' => $partnerOffer->partner->offerUrl,
                    'contentId' => $partnerOffer->partner->offerContentId,
                ],
                'productId' => $partnerOffer->productId,
                'deliveryDayCount' => $partnerOffer->deliveryDayCount,
                'stock' => array_map(function(\EnterModel\Product\Stock $stock) {
                    return [
                        'storeId' => $stock->storeId,
                        'shopId' => $stock->shopId,
                        'quantity' => $stock->quantity,
                        'showroomQuantity' => $stock->showroomQuantity,
                    ];
                }, $partnerOffer->stock),
            ];
        }, $product->partnerOffers);
    }
}