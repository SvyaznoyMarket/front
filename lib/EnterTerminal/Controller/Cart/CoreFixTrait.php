<?php

namespace EnterTerminal\Controller\Cart;

trait CoreFixTrait {
    /**
     * @param array $data
     */
    public function fixCoreResponse(array &$data) {
        $data += [
            'orders' => [],
        ];

        $orderItem = null;
        foreach ($data['orders'] as &$orderItem) {
            $orderItem += [
                'possible_point_data' => [],
                'possible_points'     => [],
            ];
            foreach ($orderItem['possible_point_data'] as $token => $possiblePointItem) {
                $orderItem['possible_points'][$token][] = $possiblePointItem['id'];
            }
        }
        unset($orderItem);
    }
}