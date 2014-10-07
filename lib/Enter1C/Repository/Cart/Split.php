<?php

namespace Enter1C\Repository\Cart;

use EnterModel as Model;

class Split {
    public function convertObjectToXmlArray(Model\Cart\Split $split) {
        $result = [
            'delivery_groups' => [],
            'delivery_methods' => [],
            'payment_methods' => [],
            'point_groups' => [],
            'orders' => [],
            'user_info' => null,
            'total_cost' => null,
            'errors' => [],
        ];

        foreach ($split->deliveryGroups as $deliveryGroup) {
            $result['delivery_groups'][$deliveryGroup->id] = [
                'delivery_group' => [
                    'id' => $deliveryGroup->id,
                    'name' => $deliveryGroup->name,
                ],
            ];
        }

        foreach ($split->deliveryMethods as $deliveryMethod) {
            $result['delivery_methods'][$deliveryMethod->token] = [
                'delivery_method' => [
                    'token' => $deliveryMethod->token,
                    'type_id' => $deliveryMethod->typeId,
                    'type_ui' => $deliveryMethod->typeUi,
                    'name' => $deliveryMethod->name,
                    'point_token' => $deliveryMethod->pointToken,
                    'group_id' => $deliveryMethod->groupId,
                    'description' => $deliveryMethod->description,
                ],
            ];
        }

        foreach ($split->paymentMethods as $paymentMethod) {
            $result['payment_methods'][$paymentMethod->id] = [
                'payment_method' => [
                    'id' => $paymentMethod->id,
                    'ui' => $paymentMethod->ui,
                    'name' => $paymentMethod->name,
                    'description' => $paymentMethod->description,
                ],
            ];
        }

        foreach ($split->pointGroups as $pointGroup) {
            $pointGroupItem = [
                'point_group' => [
                    'token' => $pointGroup->token,
                    'action_name' => $pointGroup->actionName,
                    'block_name' => $pointGroup->blockName,
                    'points' => [],
                ],
            ];

            foreach ($pointGroup->points as $point) {
                $pointItem = [
                    'point' => [
                        'id' => $point->id,
                        'ui' => $point->ui,
                        'number' => $point->number,
                        'name' => $point->name,
                        'address' => $point->address,
                        'house' => $point->house,
                        'regtime' => $point->regime,
                        'latitude' => $point->latitude,
                        'longitude' => $point->longitude,
                        'subway' => [],
                    ],
                ];

                foreach ($point->subway as $subway) {
                    $subwayItem = [
                        'subway' => [
                            'name' => $subway->name,
                            'line' => [],
                        ],
                    ];

                    if (isset($subway->line)) {
                        $subwayItem['subway']['line'] = [
                            'name' => $subway->line->name,
                            'color' => $subway->line->color,
                        ];
                    }

                    $pointItem['point']['subway'][] = $subwayItem;
                }

                $pointGroupItem['point_group']['points'][$point->id] = $pointItem;
            }

            $result['point_groups'][$pointGroup->token] = $pointGroupItem;
        }

        foreach ($split->orders as $order) {
            $orderItem = [
                'order' => [
                    'block_name' => $order->blockName,
                    'seller' => isset($order->seller) ? [
                        'id' => $order->seller->id,
                        'name' => $order->seller->name,
                        'offer' => $order->seller->offerUrl,
                    ] : null,
                    'products' => [],
                    'discounts' => [],
                    'actions' => [],
                    'delivery' => isset($order->delivery) ? [
                        'delivery_method_token' => $order->delivery->methodToken,
                        'mode_id' => $order->delivery->modeId,
                        'date' => isset($order->delivery->date) ? date('Y-m-d\TH:i:s', $order->delivery->date) : null,
                        'price' => $order->delivery->price,
                        'interval' => isset($order->delivery->interval) ? [
                            'from' => $order->delivery->interval->from,
                            'to' => $order->delivery->interval->to,
                        ] : null,
                        'point' => isset($order->delivery->point) ? [
                            'token' => $order->delivery->point->groupToken,
                            'id' => $order->delivery->point->id,
                            'ui' => $order->delivery->point->ui,
                        ] : null,
                        'use_user_address' => $order->delivery->useUserAddress ? 'true' : 'false',
                        'type_id' => $order->delivery->typeId,
                        'type_ui' => $order->delivery->typeUi,
                    ] : null,
                    'total_cost' => $order->sum,
                    'total_original_cost' => $order->originalSum,
                    'payment_method_id' => $order->paymentMethodId,
                    'possible_deliveries' => [],
                    'possible_intervals' => [],
                    'possible_days' => [],
                    'possible_payment_methods' => [],
                    'possible_point_ids' => [],
                    'possible_points' => [],
                    'comment' => $order->comment,
                ],
            ];

            foreach ($order->products as $product) {
                $orderItem['order']['products'][] = [
                    'product' => [
                        'id' => $product->id,
                        'ui' => $product->ui,
                        'name' => $product->name,
                        'prefix' => $product->namePrefix,
                        'name_web' => $product->webName,
                        'url' => $product->url,
                        'image' => $product->image,
                        'price' => $product->price,
                        'original_price' => $product->originalPrice,
                        'sum' => $product->sum,
                        'quantity' => $product->quantity,
                        'stock' => $product->stockQuantity,
                    ],
                ];
            }

            foreach ($order->discounts as $discount) {
                $orderItem['order']['discounts'][] = [
                    'discount' => [
                        'ui' => $discount->ui,
                        'name' => $discount->name,
                        'discount' => $discount->discount,
                        'type' => $discount->type,
                        'number' => $discount->number,
                    ],
                ];
            }

            foreach ($order->actions as $action) {
                $orderItem['order']['actions'][] = [
                    'action' => [
                        'ui' => $action->ui,
                        'name' => $action->name,
                        'discount' => $action->discount,
                        'type' => $action->type,
                        'number' => $action->number,
                    ],
                ];
            }

            foreach ($order->possibleDeliveryMethodTokens as $token) {
                $orderItem['order']['possible_deliveries'][] = ['delivery' => $token];
            }

            foreach ($order->possibleIntervals as $interval) {
                $orderItem['order']['possible_intervals'][] = [
                    'interval' => [
                        'from' => $interval->from,
                        'to' => $interval->to,
                    ],
                ];
            }

            foreach ($order->possibleDays as $possibleDay) {
                $orderItem['order']['possible_days'][] = ['date' => date('Y-m-d\TH:i:s', $possibleDay)];
            }

            foreach ($order->possiblePaymentMethodIds as $id) {
                $orderItem['order']['possible_payment_methods'][] = ['payment_method' => $id];
            }

            foreach ($order->groupedPossiblePointIds as $groupToken => $pointIds) {
                foreach ($pointIds as $id) {
                    $orderItem['order']['possible_points'][$groupToken][] = $id;
                }
            }

            $result['orders'][] = $orderItem;
        }

        $result['user_info'] = [
            'phone' => $split->user->phone,
            'last_name' => $split->user->lastName,
            'first_name' => $split->user->firstName,
            'email' => $split->user->email,
            'address' => isset($split->user->address) ? [
                'street' => $split->user->address->street,
                'building' => $split->user->address->building,
                'number' => $split->user->address->number,
                'apartment' => $split->user->address->apartment,
                'floor' => $split->user->address->floor,
                'metro_station' => $split->user->address->subwayName,
                'kladr_id' => $split->user->address->kladrId,
            ] : null,
            'bonus_card_number' => $split->user->bonusCardNumber,
        ];

        $result['total_cost'] = $split->sum;

        foreach ($split->errors as $error) {
            $result['errors'][] = ['error' => [
                'code' => $error->code,
                'message' => $error->message,
            ]];
        }

        // Замена связей данными

        foreach ($result['delivery_methods'] as $key => $item) {
            if ($item['delivery_method']['point_token'] != null) {
                $result['delivery_methods'][$key]['delivery_method']['point_groups'][] = $result['point_groups'][$item['delivery_method']['point_token']];
                unset($result['delivery_methods'][$key]['delivery_method']['point_token']);
            }

            if ($item['delivery_method']['group_id'] != null) {
                $result['delivery_methods'][$key]['delivery_method']['group'] = $result['delivery_groups'][$item['delivery_method']['group_id']]['delivery_group'];
                unset($result['delivery_methods'][$key]['delivery_method']['group_id']);
            }
        }

        foreach ($result['orders'] as $key => $item) {
            if ($item['order']['delivery']['delivery_method_token'] != null) {
                $result['orders'][$key]['order']['delivery']['delivery_method'] = $result['delivery_methods'][$item['order']['delivery']['delivery_method_token']]['delivery_method'];
                unset($result['orders'][$key]['order']['delivery']['delivery_method_token']);
            }

            if ($item['order']['delivery']['point']) {
                $token = $item['order']['delivery']['point']['token'];
                $id = $item['order']['delivery']['point']['id'];

                $pointGroup = $result['point_groups'][$token];
                unset($pointGroup['point_group']['points']);
                $pointGroup['point_group']['points'][] = $result['point_groups'][$token]['point_group']['points'][$id];
                $result['orders'][$key]['order']['delivery']['point_groups'][] = $pointGroup;

                unset($result['orders'][$key]['order']['delivery']['point']);
            }

            if ($item['order']['payment_method_id'] != null) {
                $result['orders'][$key]['order']['payment_method'] = $result['payment_methods'][$item['order']['payment_method_id']]['payment_method'];
                unset($result['orders'][$key]['order']['payment_method_id']);
            }

            foreach ($item['order']['possible_deliveries'] as $deliveryKey => $deliveryItem) {
                $result['orders'][$key]['order']['possible_deliveries'][$deliveryKey]['delivery'] = $result['delivery_methods'][$deliveryItem['delivery']]['delivery_method'];
            }

            foreach ($item['order']['possible_payment_methods'] as $paymentKey => $paymentItem) {
                $result['orders'][$key]['order']['possible_payment_methods'][$paymentKey]['payment_method'] = $result['payment_methods'][$paymentItem['payment_method']]['payment_method'];
            }

            foreach ($item['order']['possible_points'] as $groupToken => $pointIds) {
                $possiblePointGroup = $result['point_groups'][$groupToken];
                unset($possiblePointGroup['point_group']['points']);
                foreach ($pointIds as $pointId) {
                    $possiblePointGroup['point_group']['points'][] = $result['point_groups'][$groupToken]['point_group']['points'][$pointId];
                }

                $result['orders'][$key]['order']['possible_points'][] = $possiblePointGroup;
                unset($result['orders'][$key]['order']['possible_points'][$groupToken]);
            }
        }

        // Изменение ключей массивов на числовые для корректной последующей обработки XML конвертором

        $result['delivery_groups'] = array_values($result['delivery_groups']);
        $result['delivery_methods'] = array_values($result['delivery_methods']);
        $result['payment_methods'] = array_values($result['payment_methods']);
        $result['point_groups'] = array_values($result['point_groups']);

        foreach ($result['point_groups'] as $key => $group) {
            $result['point_groups'][$key]['point_group']['points'] = array_values($group['point_group']['points']);
        }

        return $result;
    }

    public function convertXmlArrayToCoreArray($split) {
        if (isset($split['delivery_groups']) && is_array($split['delivery_groups'])) {
            $items = [];
            foreach ($split['delivery_groups'] as $item) {
                if (isset($item['id'])) {
                    $items[$item['id']] = $item;
                }
            }

            $split['delivery_groups'] = $items;
        }

        if (isset($split['delivery_methods']) && is_array($split['delivery_methods'])) {
            $items = [];
            foreach ($split['delivery_methods'] as $item) {
                if (isset($item['group'])) {
                    if (isset($item['group']['id'])) {
                        $item['group_id'] = $item['group']['id'];
                    }

                    unset($item['group']);
                }

                if (isset($item['point_groups'])) {
                    if (isset($item['point_groups']['point_group']['token'])) {
                        $item['point_token'] = $item['point_groups']['point_group']['token'];
                    }

                    unset($item['point_groups']);
                }

                if (isset($item['token'])) {
                    $items[$item['token']] = $item;
                }
            }

            $split['delivery_methods'] = $items;
        }

        if (isset($split['payment_methods']) && is_array($split['payment_methods'])) {
            $items = [];
            foreach ($split['payment_methods'] as $item) {
                if (isset($item['id'])) {
                    $items[$item['id']] = $item;
                }
            }

            $split['payment_methods'] = $items;
        }

        if (isset($split['point_groups'])) {
            if (is_array($split['point_groups'])) {
                $pointGroups = [];
                foreach ($split['point_groups'] as $group) {
                    if (isset($group['points'])) {
                        if (is_array($group['points'])) {
                            $items = [];
                            foreach ($group['points'] as $point) {
                                if (isset($point['subway'])) {
                                    $this->correctXmlArray($point['subway']);
                                }

                                if (isset($point['id'])) {
                                    $items[$point['id']] = $point;
                                }
                            }

                            $group['points'] = $items;
                        }

                        $group['list'] = $group['points'];
                        unset($group['points']);
                    }

                    if (isset($group['token'])) {
                        $pointGroups[$group['token']] = $group;
                        unset($pointGroups[$group['token']]['token']);
                    }
                }

                $split['points'] = $pointGroups;
            }

            unset($split['point_groups']);
        }

        if (isset($split['orders']) && is_array($split['orders'])) {
            $orders = [];
            foreach ($split['orders'] as $order) {
                if (isset($order['products'])) {
                    $this->correctXmlArray($order['products']);
                }

                if (isset($order['discounts'])) {
                    $this->correctXmlArray($order['discounts']);
                }

                if (isset($order['actions'])) {
                    $this->correctXmlArray($order['actions']);
                }

                if (isset($order['delivery']['delivery_method'])) {
                    if (isset($order['delivery']['delivery_method']['token'])) {
                        $order['delivery']['delivery_method_token'] = $order['delivery']['delivery_method']['token'];
                    }

                    unset($order['delivery']['delivery_method']);
                }

                if (isset($order['delivery']['date']) && $order['delivery']['date'] != null) {
                    $order['delivery']['date'] = strtotime($order['delivery']['date']);
                }

                if (isset($order['delivery']['point_groups'])) {
                    if (isset($order['delivery']['point_groups']['point_group'])) {
                        $group = $order['delivery']['point_groups']['point_group'];
                        $order['delivery']['point'] = [
                            'token' => isset($group['token']) ? $group['token'] : null,
                            'id' => isset($group['points']['point']['id']) ? $group['points']['point']['id'] : null,
                            'ui' => isset($group['points']['point']['ui']) ? $group['points']['point']['ui'] : null,
                        ];
                    }

                    unset($order['delivery']['point_groups']);
                }

                if (isset($order['payment_method'])) {
                    if (isset($order['payment_method']['id'])) {
                        $order['payment_method_id'] = $order['payment_method']['id'];
                    }

                    unset($order['payment_method']);
                }

                if (isset($order['possible_deliveries']) && is_array($order['possible_deliveries'])) {
                    $items = [];
                    foreach ($order['possible_deliveries'] as $item) {
                        if (isset($item['token'])) {
                            $items[] = $item['token'];
                        }
                    }

                    $order['possible_deliveries'] = $items;
                }

                if (isset($order['possible_intervals'])) {
                    $this->correctXmlArray($order['possible_intervals']);
                }

                if (isset($order['possible_days']) && is_array($order['possible_days'])) {
                    $items = [];
                    foreach ($order['possible_days'] as $date) {
                        if ($date != null) {
                            $items[] = strtotime($date);
                        }
                    }

                    $order['possible_days'] = $items;
                }

                if (isset($order['possible_payment_methods']) && is_array($order['possible_payment_methods'])) {
                    $items = [];
                    foreach ($order['possible_payment_methods'] as $item) {
                        if (isset($item['id'])) {
                            $items[] = $item['id'];
                        }
                    }

                    $order['possible_payment_methods'] = $items;
                }

                $this->correctXmlArray($order['possible_point_ids']);

                if (isset($order['possible_points']) && is_array($order['possible_points'])) {
                    $items = [];
                    foreach ($order['possible_points'] as $group) {
                        if (isset($group['points']) && is_array($group['points'])) {
                            $ids = [];
                            foreach ($group['points'] as $point) {
                                if (isset($point['id'])) {
                                    $ids[] = $point['id'];
                                }
                            }

                            if (isset($group['token'])) {
                                $items[$group['token']] = $ids;
                            }
                        }
                    }

                    $order['possible_points'] = $items;
                }

                if (isset($order['block_name'])) {
                    $orders[$order['block_name']] = $order;
                }
            }

            $split['orders'] = $orders;
            $this->moveArrayElementToBottom($split, 'orders');
        }

        $this->moveArrayElementToBottom($split, 'user_info');
        $this->moveArrayElementToBottom($split, 'total_cost');
        $this->moveArrayElementToBottom($split, 'errors');

        if (isset($split['errors'])) {
            $this->correctXmlArray($split['errors']);
        }

        return $split;
    }

    private function correctXmlArray(&$array) {
        if (is_array($array)) {
            $key = key($array);
            if (!is_numeric($key) && isset($array[$key])) {
                $array[0] = $array[$key];
                unset($array[$key]);
            }
        }
    }

    private function moveArrayElementToBottom(&$array, $key) {
        if (isset($array[$key])) {
            $copy = &$array[$key];
            unset($array[$key]);
            $array[$key] = &$copy;
        }
    }
}