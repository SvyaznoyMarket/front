<?php

namespace EnterTerminal\Repository;

use Enter\Http;
use Enter\Curl\Query;
use EnterModel as Model;

class Product extends \EnterRepository\Product {
    /**
     * @param Query $query
     * @return Model\Product\Delivery[]
     */
    public function getDeliveryObjectByListQuery(Query $query) {
        $nearestDeliveries = [];

        try {
            $result = $query->getResult();
        } catch (\Exception $e) {
            return $nearestDeliveries;
        }

        $productData = &$result['product_list'];
        $shopData = &$result['shop_list'];

        $regionData = [];
        foreach ($result['geo_list'] as $regionItem) {
            $regionData[(string)$regionItem['id']] = $regionItem;
        }

        foreach ($productData as $item) {
            $productId = (string)$item['id'];

            if (!isset($item['delivery_mode_list'])) continue;
            foreach ($item['delivery_mode_list'] as $deliveryItem) {
                if (!isset($deliveryItem['date_list']) || !is_array($deliveryItem['date_list'])) continue;

                // FIXME
                if (in_array($deliveryItem['token'], ['now'])) continue;

                $delivery = new Model\Product\Delivery();
                $delivery->productId = $productId;
                $delivery->id = (string)$deliveryItem['id'];
                $delivery->token = (string)$deliveryItem['token'];
                $delivery->price = (int)$deliveryItem['price'];

                /** @var string $date Ближайшая дата доставки */
                $date = reset($deliveryItem['date_list']);
                $date = !empty($date['date']) ? $date['date'] : null;
                $delivery->nearestDeliveredAt = $date ? new \DateTime($date) : null;

                $day = 0;
                foreach ($deliveryItem['date_list'] as $dateItem) {
                    $day++;
                    if ($day > 7) break;

                    if (in_array($deliveryItem['token'], ['self', 'now'])) {
                        foreach ($dateItem['shop_list'] as $shopIntervalItem) {
                            $shopId = (string)$shopIntervalItem['id'];
                            $shopItem = (!array_key_exists($shopId, $delivery->shopsById) && isset($shopData[$shopId]['id'])) ? $shopData[$shopId] : null;
                            if (!$shopItem) continue;

                            $regionId = (string)$shopItem['geo_id'];
                            if (array_key_exists($regionId, $regionData)) {
                                $shopItem['geo'] = $regionData[$regionId];
                            }

                            $shop = new Model\Shop($shopItem);

                            $delivery->shopsById[$shopId] = $shop;
                        }
                    }
                }

                $nearestDeliveries[] = $delivery;
            }
        }

        return $nearestDeliveries;
    }
}