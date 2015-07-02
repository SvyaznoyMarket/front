<?php

namespace EnterTerminal\Repository\Cart;

class Split {
    public function correctResponse(\EnterTerminal\Model\ControllerResponse\Cart\Split $response, \EnterModel\Cart\Split $splitModel = null) {
        if (isset($response->split['orders'])) {
            $orderNum = -1;
            foreach ($response->split['orders'] as $orderToken => &$order) {
                $orderNum++;

                if ($splitModel) {
                    // Добавляем элемент media с новыми картинками товаров вместо элемента image
                    call_user_func(function() use(&$order, &$orderNum, &$splitModel) {
                        if (isset($order['products'])) {
                            foreach ($order['products'] as $productNum => &$product) {
                                $product['media'] = $splitModel->orders[$orderNum]->products[$productNum]->media;
                            }
                        }
                    });
                }

                // Подмешиваем URL картинок для типов точек самовывоза
                call_user_func(function() use(&$response) {
                    $pointRepository = new \EnterRepository\Point();
                    foreach ($response->split['points'] as $pointToken => &$pointGroup) {
                        $pointGroup['media'] = $pointRepository->getMedia($pointToken);
                    }
                });

                // Создаём фильтры точек самовывоза
                call_user_func(function() use(&$response, &$orderToken, &$order) {
                    $response->pointFilters[$orderToken] = [
                        'type' => [],
                        'cost' => [],
                        'nearestDay' => [],
                    ];

                    foreach ($order['possible_point_data'] as $pointType => $points) {
                        if (!isset($response->pointFilters[$orderToken]['type'][$pointType])) {
                            switch ($pointType) {
                                case 'self_partner_pickpoint_pred_supplier':
                                case 'self_partner_pickpoint':
                                    $name = 'Пункты выдачи Pickpoint';
                                    break;
                                case 'self_partner_svyaznoy_pred_supplier':
                                case 'self_partner_svyaznoy':
                                case 'shops_svyaznoy':
                                    $name = 'Магазины Связной';
                                    break;
                                case 'self_partner_euroset_pred_supplier':
                                case 'self_partner_euroset':
                                    $name = 'Магазины Евросеть';
                                    break;
                                case 'shops':
                                    $name = 'Магазины Enter';
                                    break;
                                default:
                                    $name = $response->split['points'][$pointType]['block_name'];
                            }

                            $response->pointFilters[$orderToken]['type'][$pointType] = [
                                'name' => $name,
                                'value' => $pointType,
                            ];
                        }

                        foreach ($points as $point) {
                            if (!isset($response->pointFilters[$orderToken]['cost'][$point['cost']])) {
                                $response->pointFilters[$orderToken]['cost'][$point['cost']] = [
                                    'name' => $point['cost'] == 0 ? 'Бесплатно' : (string)$point['cost'],
                                    'value' => (string)$point['cost'],
                                ];
                            }

                            if (!isset($response->pointFilters[$orderToken]['nearestDay'][$point['nearest_day']])) {
                                $response->pointFilters[$orderToken]['nearestDay'][$point['nearest_day']] = [
                                    'name' => \Enter\Util\Date::humanizeDate(\DateTime::createFromFormat('Y-m-d', $point['nearest_day'])),
                                    'value' => $point['nearest_day'],
                                ];
                            }
                        }
                    }

                    $response->pointFilters[$orderToken]['type'] = array_values($response->pointFilters[$orderToken]['type']);
                    $response->pointFilters[$orderToken]['cost'] = array_values($response->pointFilters[$orderToken]['cost']);
                    $response->pointFilters[$orderToken]['nearestDay'] = array_values($response->pointFilters[$orderToken]['nearestDay']);
                });
            }
        }
    }

    public function dumpSplitChange($change) {
        if (isset($change['orders'])) {
            foreach ($change['orders'] as &$order) {
                if (isset($order['products'])) {
                    foreach ($order['products'] as &$product) {
                        unset($product['media']);
                    }
                }
            }
        }

        return $change;
    }
}