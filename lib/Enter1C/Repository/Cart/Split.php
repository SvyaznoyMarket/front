<?php

namespace Enter1C\Repository\Cart;

use Enter\Curl\Query;
use EnterModel as Model;

class Split {
    public function dumpObject(Model\Cart\Split $split) {
        $result = [
            'orders' => [],
        ];

        foreach ($split->orders as $order) {
            $orderItem = [
                'block_name'                => $order->name,
                'seller'                    => $order->seller ? [
                    'ui'   => $order->seller->id,
                    'name' => $order->seller->name,
                ] : null,
                'products'                  => [],
                'total_cost'                => $order->sum,
                //'payment_method_id'         => $order->paymentMethodId,
                'payment_method'            => $order->paymentMethodId ? $split->paymentMethods[$order->paymentMethodId]->dump() : null,
                'possible_delivery_methods' => array_map(function($token) use (&$split) {
                    return ['delivery_method' => $this->dumpDeliveryMethodObject($split->deliveryMethods[$token], $split)];
                }, $order->possibleDeliveryMethodTokens),
                'possible_intervals'        => array_map(function(Model\Cart\Split\Interval $interval) {
                    return ['interval' => $interval->dump()];
                }, $order->possibleIntervals),
                'possible_dates'            => array_map(function($date) {
                    return ['date' => (new \DateTime())->setTimestamp($date)->format('Y-m-d\TH:i:s')];
                }, $order->possibleDays),
            ];

            if ($order->delivery && ($deliveryMethod = $split->deliveryMethods[$order->delivery->methodToken])) {
                $orderItem['delivery'] = [
                    //'delivery_method_token' => $order->delivery->methodToken,
                    'date'                  => $order->delivery->date ? (new \DateTime())->setTimestamp($order->delivery->date)->format('Y-m-d\TH:i:s') : null,
                    'price'                 => $order->delivery->price,
                    'interval'              => $order->delivery->interval ? $order->delivery->interval->dump() : null,
                    'point'                 => $order->delivery->point ? $order->delivery->point->dump() : null,
                    'use_user_address'      => $order->delivery->hasUserAddress,
                    'delivery_method'       => $this->dumpDeliveryMethodObject($deliveryMethod, $split),
                ];
            }

            foreach ($order->products as $product) {
                $orderItem['products'][] = [
                    'product' => [
                        'id'             => $product->id,
                        'ui'             => $product->ui,
                        'name'           => $product->name,
                        'price'          => $product->price,
                        'original_price' => $product->originalPrice,
                        'sum'            => $product->sum,
                        'quantity'       => $product->quantity,
                        'stock'          => $product->stockQuantity,
                    ]
                ];
            }

            $result['orders'][] = ['order' => $orderItem];
        }

        return $result;
    }

    /**
     * @param Model\Cart\Split\DeliveryMethod $deliveryMethod
     * @param Model\Cart\Split $split
     * @return array
     */
    public function dumpDeliveryMethodObject(Model\Cart\Split\DeliveryMethod $deliveryMethod, Model\Cart\Split $split) {
        $result = [
            'token'       => $deliveryMethod->token,
            'type_id'     => $deliveryMethod->typeId,
            'name'        => $deliveryMethod->name,
            'point_token' => $deliveryMethod->pointToken,
            //'group_id'    => $deliveryMethod->groupId,
            'description' => $deliveryMethod->description,
            'group'       => $split->deliveryGroups[$deliveryMethod->groupId],
        ];

        return $result;
    }
}