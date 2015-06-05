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

            $orderItem['possible_points'] = [];
            foreach ($orderItem['possible_point_data'] as $token => $possiblePointItems) {
                $orderItem['possible_points'][$token] = array_column($possiblePointItems, 'id');
            }
        }
        unset($orderItem);
    }
}