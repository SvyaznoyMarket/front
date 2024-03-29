<?php

namespace EnterMobile\Repository\Page\Order;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterAggregator\DateHelperTrait;
use EnterMobile\TemplateRepositoryTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Order\Delivery as Page;

class Delivery {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait, PriceHelperTrait, TranslateHelperTrait, DateHelperTrait, TemplateRepositoryTrait;

    /**
     * @param Page $page
     * @param Delivery\Request $request
     */
    public function buildObjectByRequest(Page $page, Delivery\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();
        $priceHelper = $this->getPriceHelper();
        $translateHelper = $this->getTranslateHelper();
        $dateHelper = $this->getDateHelper();
        $pointRepository = new Repository\Partial\Point();
        $cartRepository = new \EnterRepository\Cart();

        // заголовок
        $page->title = 'Оформление заказа - Способ получения - Enter';

        $page->dataModule = 'order-delivery';

        $page->content->region = [
            'name' => $request->region->name,
        ];

        $page->content->deliveryForm['url'] = $router->getUrlByRoute(new Routing\Order\Delivery(), ['shopId' => $request->shopId]);

        $page->content->form->url = $router->getUrlByRoute(new Routing\Order\Create(), ['shopId' => $request->shopId]);
        $page->content->form->errorDataValue = $templateHelper->json($request->formErrors);

        $regionModel = $request->region;
        $splitModel = $request->split;

        // индексация токенов методов доставки по группам доставки
        $deliveryMethodTokensByGroupToken = [];
        foreach ($splitModel->deliveryMethods as $deliveryMethodModel) {
            $deliveryMethodTokensByGroupToken[$deliveryMethodModel->groupId][] = $deliveryMethodModel->token;
        }

        // индексация методов оплат
        /** @var \EnterModel\Cart\Split\PaymentMethod[] $paymentMethodsById */
        $paymentMethodsById = [];
        foreach ($splitModel->paymentMethods as $paymentMethod) {
            $paymentMethodsById[$paymentMethod->id] = $paymentMethod;
        }

        // индексация групп точек и точек самовывоза
        $pointGroupByTokenIndex = [];
        $pointByGroupAndIdIndex = [];
        foreach ($splitModel->pointGroups as $groupIndex => $pointGroupModel) {
            $pointGroupByTokenIndex[$pointGroupModel->token] = $groupIndex;
            foreach ($pointGroupModel->points as $pointIndex => $pointModel) {
                $pointByGroupAndIdIndex[$pointGroupModel->token][$pointModel->id] = $pointIndex;
            }
        }

        $i = 1;
        foreach ($splitModel->orders as $orderModel) {
            /** @var \EnterModel\Cart\Split\DeliveryGroup|null $deliveryGroupModel */
            $deliveryGroupModel = call_user_func(function() use (&$splitModel, &$orderModel, &$deliveryMethodTokensByGroupToken) {
                foreach ($splitModel->deliveryGroups as $deliveryGroupModel) {
                    if (
                        isset($deliveryMethodTokensByGroupToken[$deliveryGroupModel->id])
                        && in_array($orderModel->delivery->methodToken, $deliveryMethodTokensByGroupToken[$deliveryGroupModel->id])
                    ) {
                        return $deliveryGroupModel;
                    }
                }
            });
            if (!$deliveryGroupModel) continue;

            $order = [
                'id'             => $orderModel->blockName,
                'name'           => sprintf('Заказ №%s', $i),
                'seller'         =>
                    $orderModel->seller
                    ? [
                        'name' => $orderModel->seller->name,
                        'url'  => str_replace('www.enter.ru', 'm.enter.ru', $orderModel->seller->offerUrl),
                    ]
                    : false
                ,
                'sum'            => [
                    'name'  => $priceHelper->format($orderModel->sum),
                    'value' => $orderModel->sum,
                ],
                'delivery'       => call_user_func(function() use (&$templateHelper, &$priceHelper, &$dateHelper, &$splitModel, &$orderModel, &$deliveryGroupModel, &$pointGroupByTokenIndex, &$pointByGroupAndIdIndex, &$pointRepository) {
                    $delivery = false;

                    if ($deliveryModel = $orderModel->delivery) {
                        // группа точки
                        $pointGroup = null;
                        // точка
                        $point = null;
                        // если выбрана точка получения заказа ...
                        if ($deliveryModel->point) {
                            $pointGroup =
                                isset($pointGroupByTokenIndex[$deliveryModel->point->groupToken])
                                ? $splitModel->pointGroups[
                                    $pointGroupByTokenIndex[$deliveryModel->point->groupToken]
                                ]
                                : null
                            ;
                            $point =
                                ($pointGroup && isset($pointByGroupAndIdIndex[$pointGroup->token][$deliveryModel->point->id]))
                                ? $pointGroup->points[
                                    $pointByGroupAndIdIndex[$deliveryModel->point->groupToken][$deliveryModel->point->id]
                                ]
                                : null
                            ;
                            if (!$point) {
                                $this->getLogger()->push(['type' => 'error', 'message' => 'Точка не найдена', 'pointId' => $deliveryModel->point->id, 'group' => $deliveryModel->point->groupToken, 'sender' => __FILE__ . ' ' . __LINE__, 'tag' => ['order.split', 'critical']]);
                            }
                        }

                        $possiblePointModel = null;
                        if ($point) {
                            foreach ($orderModel->possiblePoints as $iPossiblePointModel) { // FIXME
                                if ($point->id === $iPossiblePointModel->id) {
                                    $possiblePointModel = $iPossiblePointModel;
                                    break;
                                }
                            }
                        }

                        // дата и интервал дат
                        $dateFrom = null;
                        $dateTo = null;
                        try {
                            $dateFrom = ($possiblePointModel && $possiblePointModel->dateInterval && $possiblePointModel->dateInterval->from) ? new \DateTime($possiblePointModel->dateInterval->from) : null;
                            $dateTo = ($possiblePointModel && $possiblePointModel->dateInterval && $possiblePointModel->dateInterval->to) ? new \DateTime($possiblePointModel->dateInterval->to) : null;
                        } catch (\Exception $e) {}

                        $delivery = [
                            'isStandart'  => 2 == $deliveryGroupModel->id,
                            'isSelf'      => 1 == $deliveryGroupModel->id,
                            'name'        => $deliveryGroupModel->name,
                            'price'       => [
                                'isCurrency' => $deliveryModel->price > 0,
                                'name'       => ($deliveryModel->price > 0) ? $priceHelper->format($deliveryModel->price) : 'Бесплатно',
                                'value'      => $deliveryModel->price,
                            ],
                            'point'       =>
                                $point
                                ? [
                                    'id'      => $point->id,
                                    'name'    => $point->name,
                                    'group'   => [
                                        'token'     => $pointGroup->token,
                                        'shortName' => $pointGroup->blockName,
                                        'name'      => $pointRepository->translateGroupName($pointGroup->blockName),
                                    ],
                                    'address' => $point->address,
                                    'icon'    => $pointRepository->getIconByType($pointGroup->token),
                                    'subway'  =>
                                        isset($point->subway[0])
                                            ? [
                                            'name'  => $point->subway[0]->name,
                                            'color' => isset($point->subway[0]->line) ? $point->subway[0]->line->color : false,
                                        ]
                                            : false
                                    ,
                                    'regime'  => $point->regime,
                                    'date'    => $orderModel->possibleDays
                                        ? call_user_func(function() use (&$date, &$dateFrom, &$dateTo, &$dateHelper) {
                                            $data = false;

                                            if ($dateFrom) {
                                                $data = [
                                                    'name'  => sprintf('%s %s', 'с ' . $dateFrom->format('d.m'), $dateTo ? (' по ' . $dateTo->format('d.m')) : ''),
                                                    'value' => $dateFrom->getTimestamp(),
                                                ];
                                            }

                                            return $data;
                                        })
                                        : null
                                    ,
                                    'order'   => [
                                        'id' => $orderModel->blockName,
                                    ],
                                ]
                                : false
                            ,
                            'isCompleted' =>
                                ((bool)$point && (1 == $deliveryGroupModel->id))
                                || ($splitModel->user && $splitModel->user->address && $splitModel->user->address->street && (2 == $deliveryGroupModel->id))
                            ,
                            'date'        =>
                                $orderModel->possibleDays
                                ? (
                                    $deliveryModel->date
                                    ? mb_strtolower($dateHelper->strftimeRu('%e %B2 %G', $deliveryModel->date)) // если нужен день недели, то '%e %B2 %G, %A'
                                    : 'Выбрать'
                                )
                                : null
                            ,
                            'interval'    =>
                                $deliveryModel->interval
                                ? [
                                    'from' => $deliveryModel->interval->from,
                                    'to'   => $deliveryModel->interval->to,
                                ]
                                : false
                            ,
                            'intervals'   => array_map(
                                function(\EnterModel\Cart\Split\Interval $interval) use (&$templateHelper, &$orderModel) {
                                    return [
                                        'from'      => $interval->from,
                                        'to'        => $interval->to,
                                        //'isActive'  => ($interval->from === $deliveryModel->interval->from) && ($interval->to === $deliveryModel->interval->to),
                                        'dataValue' => $templateHelper->json([
                                            'change' => [
                                                'orders' => [
                                                    [
                                                        'blockName' => $orderModel->blockName,
                                                        'delivery'  => [
                                                            'interval' => [
                                                                'from' => $interval->from,
                                                                'to'   => $interval->to,
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ]),
                                    ];
                                },
                                $orderModel->possibleIntervals
                            ),
                        ];
                    }

                    return $delivery;
                }),
                'deliveries'     => call_user_func(function() use (&$templateHelper, &$priceHelper, &$splitModel, &$orderModel, &$deliveryMethodTokensByGroupToken) {
                    $deliveries = [];

                    foreach ($splitModel->deliveryGroups as $deliveryGroupModel) {
                        // схождение методов доставок для данной группы доставок и данного заказа
                        $intersectTokens = array_values(
                            array_intersect(
                                $orderModel->possibleDeliveryMethodTokens,
                                $deliveryMethodTokensByGroupToken[$deliveryGroupModel->id]
                            )
                        );

                        $deliveryMethodToken = isset($intersectTokens[0]) ? $intersectTokens[0] : null;
                        if (!$deliveryMethodToken) continue;

                        $deliveries[] = [
                            'dataValue'  => $templateHelper->json([
                                'change' => [
                                    'orders' => [
                                        [
                                            'blockName' => $orderModel->blockName,
                                            'delivery'  => [
                                                'methodToken' => $deliveryMethodToken,
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                            'name'       => $deliveryGroupModel->name,
                            'isActive'   => $orderModel->delivery && in_array($orderModel->delivery->methodToken, $deliveryMethodTokensByGroupToken[$deliveryGroupModel->id]),
                        ];
                    }

                    return $deliveries;
                }),
                'products'       => call_user_func(function() use (&$templateHelper, &$priceHelper, &$splitModel, &$orderModel) {
                    $products = [];

                    foreach ($orderModel->products as $productModel) {
                        $products[] = [
                            'namePrefix' => $productModel->namePrefix,
                            'name'       => $productModel->webName,
                            'quantity'   => $productModel->quantity,
                            'price'      => [
                                'name'  => $priceHelper->format($productModel->originalPrice),
                                'value' => $productModel->originalPrice,
                            ],
                            'sum'        => [
                                'name'  => $priceHelper->format($productModel->originalSum),
                                'value' => $productModel->originalSum,
                            ],
                            'url'        => $productModel->url,
                            'image'      =>
                                isset($productModel->media->photos[0])
                                ? (new \EnterRepository\Media())->getSourceObjectByItem($productModel->media->photos[0], 'product_160')->url
                                : null
                            ,
                        ];
                    }

                    return $products;
                }),
                'discounts'      => call_user_func(function() use (&$templateHelper, &$priceHelper, &$splitModel, &$orderModel) {
                    $discounts = [];

                    foreach ($orderModel->discounts as $discountModel) {
                        $discounts[] = [
                            'name'            => $discountModel->name,
                            'discount'        => [
                                'value'      => $discountModel->discount,
                                'isCurrency' => true,
                            ],
                            'deleteDataValue' => $templateHelper->json([
                                'change' => [
                                    'orders' => [
                                        [
                                            'blockName' => $orderModel->blockName,
                                            'discounts' => [
                                                [
                                                    'number' => $discountModel->number,
                                                    'delete' => true,
                                                ],
                                            ],
                                        ]
                                    ],
                                ],
                            ]),
                        ];
                    }

                    if ($orderModel->certificate) {
                        $discounts[] = [
                            'name'            => 'Подарочный сертификат',
                            'discount'        => [
                                'value'      => $orderModel->certificate->par,
                                'isCurrency' => true,
                            ],
                            'deleteDataValue' => $templateHelper->json([
                                'change' => [
                                    'orders' => [
                                        [
                                            'blockName' => $orderModel->blockName,
                                            'certificate' => [
                                                'delete' => true,
                                            ],
                                        ]
                                    ],
                                ],
                            ]),
                        ];
                    }

                    return $discounts;
                }),
                'hasDiscountLink' => call_user_func(function() use (&$orderModel, $config) {
                    $sellerModel = $orderModel->seller;

                    return $config->discountCodes->enabled && (!$sellerModel || (in_array($sellerModel->ui, [$sellerModel::UI_ENTER, $sellerModel::UI_SORDEX], true)));
                }),
                'pointJson'      => json_encode(call_user_func(function() use (&$templateHelper, &$priceHelper, &$dateHelper, &$splitModel, &$regionModel, &$orderModel, &$pointGroupByTokenIndex, &$pointByGroupAndIdIndex, &$pointRepository) {
                    $points = [];
                    $filtersByToken =
                        [
                            'type' => [],
                            'cost' => [],
                        ]
                        + ($orderModel->possibleDays ? ['date' => []] : [])
                    ;
                    $uniqueFitsAllProductsValuesOfPoints = [];

                    foreach ($orderModel->possiblePoints as $possiblePointModel) {
                        // группа точки
                        $pointGroup =
                            isset($pointGroupByTokenIndex[$possiblePointModel->groupToken])
                            ? $splitModel->pointGroups[
                                $pointGroupByTokenIndex[$possiblePointModel->groupToken]
                            ]
                            : null
                        ;
                        // точка
                        $point =
                            ($pointGroup && isset($pointByGroupAndIdIndex[$pointGroup->token][$possiblePointModel->id]))
                            ? $pointGroup->points[
                                $pointByGroupAndIdIndex[$possiblePointModel->groupToken][$possiblePointModel->id]
                            ]
                            : null
                        ;
                        if (!$point) {
                            $this->getLogger()->push(['type' => 'error', 'message' => 'Точка не найдена', 'pointId' => $possiblePointModel->id, 'group' => $possiblePointModel->groupToken, 'sender' => __FILE__ . ' ' . __LINE__, 'tag' => ['order.split', 'critical']]);
                            continue;
                        }
                        if (!$point->latitude || !$point->longitude) {
                            $this->getLogger()->push(['type' => 'error', 'message' => 'Не заданы координаты точки', 'pointId' => $possiblePointModel->id, 'group' => $possiblePointModel->groupToken, 'sender' => __FILE__ . ' ' . __LINE__, 'tag' => ['order.split', 'critical']]);
                            continue;
                        }

                        // дата и интервал дат
                        $date = null;
                        $dateFrom = null;
                        $dateTo = null;
                        try {
                            $date = ($orderModel->possibleDays && $possiblePointModel->nearestDay) ? new \DateTime($possiblePointModel->nearestDay) : null;
                            $dateFrom = ($possiblePointModel->dateInterval && $possiblePointModel->dateInterval->from) ? new \DateTime($possiblePointModel->dateInterval->from) : null;
                            $dateTo = ($possiblePointModel->dateInterval && $possiblePointModel->dateInterval->to) ? new \DateTime($possiblePointModel->dateInterval->to) : null;
                        } catch (\Exception $e) {}

                        if (!in_array($possiblePointModel->fitsAllProducts, $uniqueFitsAllProductsValuesOfPoints, true)) {
                            $uniqueFitsAllProductsValuesOfPoints[] = $possiblePointModel->fitsAllProducts;
                        }
                        
                        $point = [
                            'id'        => $possiblePointModel->id,
                            'name'      => $point->name,
                            'fitsAllProducts'     => [
                                'name'  => '',
                                'value' => $possiblePointModel->fitsAllProducts,
                            ],
                            'group'     => [
                                'name'  => $pointRepository->translateGroupName($pointGroup->blockName),
                                'value' => $pointGroup->token,
                            ],
                            'icon'      => $pointRepository->getIconByType($pointGroup->token),
                            'date'      =>
                                $orderModel->possibleDays
                                ? call_user_func(function() use (&$date, &$dateFrom, &$dateTo, &$dateHelper) {
                                    if ($dateFrom) {
                                        $data = [
                                            'name'  => sprintf('%s %s', 'с ' . $dateFrom->format('d.m'), $dateTo ? (' по ' . $dateTo->format('d.m')) : ''),
                                            'value' => $dateFrom->getTimestamp(),
                                        ];
                                    } else {
                                        $data = [
                                            'name'  => $date ? $dateHelper->humanizeDate($date) : null,
                                            'value' => $date ? $date->getTimestamp() : null,
                                        ];
                                    }

                                    return $data;
                                })
                                : null,
                            'address'   => $point->address,
                            'cost'      => [
                                'name'  => $possiblePointModel->cost ?: false,
                                'value' => $possiblePointModel->cost,
                            ]
                            ,
                            'subway'    =>
                                isset($point->subway[0])
                                ? [
                                    'name'  => $point->subway[0]->name,
                                    'color' => isset($point->subway[0]->line) ? $point->subway[0]->line->color : false,
                                ]
                                : false
                            ,
                            'regime' => $point->regime,
                            'lat'    => $point->latitude,
                            'lng'    => $point->longitude,
                            'dataValue'  => $templateHelper->json([
                                'change' => [
                                    'orders' => [
                                        [
                                            'blockName' => $orderModel->blockName,
                                            'delivery'  => [
                                                'point' => [
                                                    'id'         => $possiblePointModel->id,
                                                    'groupToken' => $possiblePointModel->groupToken,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ]),
                        ];
                        $points[] = $point;
                        // фильтр по типу точки
                        if (!isset($filtersByToken['type'][$pointGroup->blockName])) {
                            $filtersByToken['type'][$pointGroup->blockName] = [
                                'id'        => $pointGroup->token,
                                'name'      => $pointGroup->blockName,
                                'checked' => false,
                                'dataValue' => $templateHelper->json([
                                    'name'  => 'group',
                                    'value' => $pointGroup->token,
                                    'type' => 'array',
                                ]),
                            ];
                        }
                        // фильтр по цене
                        if (!isset($filtersByToken['cost'][$possiblePointModel->cost])) {
                            $filtersByToken['cost'][$possiblePointModel->cost] = [
                                'id'        => $possiblePointModel->cost ?: uniqid(),
                                'name'      => $possiblePointModel->cost ?: false,
                                'checked' => false,
                                'dataValue' => $templateHelper->json([
                                    'name'  => 'cost',
                                    'value' => $possiblePointModel->cost,
                                    'type' => 'array',
                                ]),
                            ];
                        }
                        // фильтр по дате
                        if ($date && !isset($filtersByToken['date'][$point['date']['value']])) {
                            $filtersByToken['date'][$point['date']['value']] = [
                                'id'        => $point['date']['value'],
                                'name'      => $point['date']['name'],
                                'checked' => false,
                                'dataValue' => $templateHelper->json([
                                    'name'  => 'date',
                                    'value' => $point['date']['value'],
                                    'type' => 'array',
                                ]),
                            ];
                        }
                    }
                    
                    if (count($uniqueFitsAllProductsValuesOfPoints) > 1) {
                        $filtersByToken['fitsAllProducts'][] = [
                            'id'        => '1',
                            'name'      => 'Оптимально для заказа',
                            'checked' => true,
                            'dataValue' => $templateHelper->json([
                                'name'  => 'fitsAllProducts',
                                'value' => true,
                                'type' => 'bool',
                            ]),
                        ];
                    }

                    // convert filter format
                    $filters = array_map(
                        function($filter) {
                            return array_values($filter);
                        },
                        $filtersByToken
                    );

                    return [
                        'points'       => $points,
                        'filters'      => $filters,
                        'order'        => [
                            'id' => $orderModel->blockName,
                        ],
                        'mapDataValue' => $templateHelper->json([
                            'center' => [
                                'lat' => $regionModel->latitude,
                                'lng' => $regionModel->longitude,
                            ],
                            'zoom'   => 10,
                        ]),
                    ];
                }), JSON_UNESCAPED_UNICODE),
                'dateJson'       =>
                    $orderModel->possibleDays
                    ? json_encode(call_user_func(function() use (&$templateHelper, &$dateHelper, &$splitModel, &$orderModel) {
                        $items = [];

                        try {
                            $possibleDays = $orderModel->possibleDays;
                            $lastAvailableDay = \DateTime::createFromFormat('U', (string)end($possibleDays));
                            $firstAvailableDay = \DateTime::createFromFormat('U', (string)reset($possibleDays));
                            $week = (0 == $firstAvailableDay->format('w')) ?  'previous week' : 'this week';
                            $firstDayOfAvailableWeek = \DateTime::createFromFormat('U', strtotime($week, $firstAvailableDay->format('U')));
                            $lastDayOfAvailableMonth = \DateTime::createFromFormat('U', strtotime('Monday next week', $lastAvailableDay->format('U')));
                            $days = new \DatePeriod($firstDayOfAvailableWeek, new \DateInterval('P1D'), $lastDayOfAvailableMonth);
                            $currentMonth = null;

                            foreach ($days as $day) {
                                /** @var $day \DateTime */
                                if ($currentMonth != $day->format('F')) {
                                    $isMonday = $day->format('N') == 1;
                                    if (!$isMonday) { // TODO: выяснить зачем это нужно
                                        for ($i = 0; $i < 8 - $day->format('N'); $i++) {
                                            $items[] = [
                                                'isDisabled' => true,
                                            ];
                                        }
                                    }
                                    $items[] = [
                                        'isMonth' => true,
                                        'name'    => strftime('%B', $day->format('U')),
                                    ];

                                    $currentMonth = $day->format('F');
                                    if (!$isMonday) { // TODO: выяснить зачем это нужно
                                        for ($i = 1; $i < $day->format('N'); $i++) {
                                            $items[] = [
                                                'isDisabled' => true,
                                            ];
                                        }
                                    }
                                }

                                $item = [
                                    'name' => $day->format('d'),
                                ];
                                if (in_array((int)$day->format('U'), $possibleDays)) {
                                    if ($firstAvailableDay == $day) {
                                        $item['isFirst'] = true;
                                    }

                                    $item['dataValue'] = $templateHelper->json([
                                        'change' => [
                                            'orders' => [
                                                [
                                                    'blockName' => $orderModel->blockName,
                                                    'delivery'  => [
                                                        'date' => $day->format('U'),
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ]);
                                } else {
                                    $item['isDisabled'] = true;
                                }
                                $items[] = $item;
                            }

                        } catch (\Exception $e) {
                            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'order.blockName' => $orderModel->blockName, 'sender' => __FILE__ . ' ' . __LINE__, 'tag' => ['order.split', 'critical']]);
                        }

                        return [
                            'items' => $items,
                        ];
                    }), JSON_UNESCAPED_UNICODE)
                    : null
                ,
                'paymentMethods' => call_user_func(function() use (&$templateHelper, &$splitModel, &$orderModel, &$paymentMethodsById) {
                    $paymentMethods = [];
                    $paymentMethodImagesById = (new \EnterMobile\Repository\Order())->getPaymentImagesByPaymentMethodId();

                    foreach ($orderModel->possiblePaymentMethods as $possiblePaymentMethod) {
                        /** @var \EnterModel\Cart\Split\PaymentMethod|null $paymentMethodModel */
                        $paymentMethodModel = isset($paymentMethodsById[$possiblePaymentMethod->id]) ? $paymentMethodsById[$possiblePaymentMethod->id] : null;
                        if (!$paymentMethodModel) {
                            $this->getLogger()->push(['type' => 'error', 'message' => 'Метод оплаты не найден', 'possiblePaymentMethod' => $possiblePaymentMethod, 'sender' => __FILE__ . ' ' . __LINE__, 'tag' => ['order.split', 'critical']]);

                            continue;
                        }

                        $paymentMethods[] = [
                            'id'          => $paymentMethodModel->id,
                            'name'        => $paymentMethodModel->name . (!$paymentMethodModel->isOnline ? ' при получении' : ''),
                            'description' => $paymentMethodModel->description,
                            'isActive'    =>
                                $orderModel->paymentMethodId
                                ? ($orderModel->paymentMethodId == $paymentMethodModel->id)
                                : false
                            ,
                            'dataValue'   => $templateHelper->json([
                                'change' => [
                                    'orders' => [
                                        [
                                            'blockName'       => $orderModel->blockName,
                                            'paymentMethodId' => $paymentMethodModel->id,
                                        ],
                                    ],
                                ],
                            ]),
                            'order'       => [
                                'id' => $orderModel->blockName,
                            ],
                            'isOnline'    => $paymentMethodModel->isOnline,
                            'discount'    =>
                                $possiblePaymentMethod->discount
                                ? [
                                    'name' => $possiblePaymentMethod->discount->value,
                                    'unit' => ('rub' === $possiblePaymentMethod->discount->unit) ? 'руб.' : $possiblePaymentMethod->discount->unit,
                                ]
                                : false
                            ,
                            'image'       => @$paymentMethodImagesById[$paymentMethodModel->id],
                        ];
                    }

                    if (
                        isset($paymentMethods[0])
                        && (
                            !$orderModel->paymentMethodId
                            || (1 === count($paymentMethods))
                        )
                    ) {
                        $paymentMethods[0]['isActive'] = true;
                    }

                    return $paymentMethods;
                }),
                'hasOnlineDiscount' => call_user_func(function() use (&$paymentMethodsById, &$orderModel) {
                    $return = false;

                    foreach ($orderModel->possiblePaymentMethods as $possiblePaymentMethodModel) {
                        /** @var \EnterModel\Cart\Split\PaymentMethod|null $paymentMethod */
                        $paymentMethod = isset($paymentMethodsById[$possiblePaymentMethodModel->id]) ? $paymentMethodsById[$possiblePaymentMethodModel->id] : null;

                        if ($paymentMethod && $paymentMethod->isOnline && $paymentMethod->discount) {
                            $return = true;
                            break;
                        }
                    }

                    return $return;
                }),
                'messages'       => call_user_func(function() use (&$config, &$orderModel, &$priceHelper) {
                    $messages = [];

                    // предоплата
                    if (
                        $config->order->prepayment->enabled
                        && !empty($orderModel->prepaidSum)
                    ) {
                        $messages[] = [
                            'isPrepayment' => true,
                        ];
                    }

                    return $messages;
                }),
                'addressFormJson' => json_encode([
                    'url'    => $router->getUrlByRoute(new Routing\Order\Delivery(), ['shopId' => $request->shopId]),
                    'fields' => [
                        'street'     => [
                            'name' => 'change[user][address][street]',
                        ],
                        'streetType' => [
                            'name' => 'change[user][address][streetType]',
                        ],
                        'building'   => [
                            'name' => 'change[user][address][building]',
                        ],
                        'apartment'  => [
                            'name' => 'change[user][address][apartment]',
                        ],
                        'kladrId'    => [
                            'name' => 'change[user][address][kladrId]',
                        ],
                    ],
                    'order' => [
                        'id' => $orderModel->blockName,
                    ],
                    'mapDataValue' => $templateHelper->json([
                        'center' => [
                            'lat' => $regionModel->latitude,
                            'lng' => $regionModel->longitude,
                        ],
                        'zoom'   => 10,
                    ]),
                ], JSON_UNESCAPED_UNICODE),
                'discountFormJson' => json_encode([
                    'url'       => $router->getUrlByRoute(new Routing\Order\Delivery(), ['shopId' => $request->shopId]),
                    'checkUrl'  => $router->getUrlByRoute(new Routing\Certificate\Check()),
                    'couponUrl' => $request->user ? $router->getUrlByRoute(new Routing\User\Coupon\Get()) : '',
                    'fields'    => [
                        'number' => [
                            'name' => 'change[orders][0][discounts][0][number]',
                        ],
                        'pin'    => [
                            'name' => 'change[orders][0][discounts][0][pin]',
                        ],
                        'order'  => [
                            'name'  => 'change[orders][0][blockName]',
                            'value' => $orderModel->blockName,
                        ],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'user'           => [
                    'address'     =>
                        ($splitModel->user && $splitModel->user->address && $splitModel->user->address->street)
                        ? call_user_func(function() use (&$splitModel, &$regionModel) {
                            $name = $regionModel->name;
                            if ($splitModel->user->address->street) {
                                $name .= ', ' . $splitModel->user->address->street;
                            }
                            if ($splitModel->user->address->building) {
                                $name .= ', д. ' . $splitModel->user->address->building;
                            }
                            if ($splitModel->user->address->apartment) {
                                $name .= ', кв. ' . $splitModel->user->address->apartment;
                            }

                            $address = [
                                'name'      => $name,
                                'street'    => $splitModel->user->address->street,
                                'building'  => $splitModel->user->address->building,
                                'apartment' => $splitModel->user->address->apartment,
                                'kladrId'   => $splitModel->user->address->kladrId,
                            ];

                            return $address;
                        })
                        : false

                ],
            ];

            $page->content->orders[] = $order;

            $i++;
        }

        $orderCount = count($splitModel->orders);
        $page->content->orderCountMessage =
            $orderCount > 1
            ? ($orderCount . ' ' . $translateHelper->numberChoice($orderCount, ['отдельный заказ', 'отдельных заказа', 'отдельных заказов']))
            : false
        ;

        $page->content->orderRemainSum = $regionModel ? (new \EnterRepository\Order())->getRemainSum($cartRepository->getSplitProductsSum($splitModel), $regionModel) : null;

        $page->content->dataValue = $templateHelper->json([
            'order' => [
                'count' => count($splitModel->orders),
            ]
        ]);

        $page->content->errors = call_user_func(function() use (&$splitModel, &$request) {
            $errors = [];

            foreach ($splitModel->errors as $errorModel) {
                if (isset($errorModel->detail['product']['name'])) {
                    $message = $errorModel->message . ' ' . $errorModel->detail['product']['name'];
                } else {
                    $message = $errorModel->message;
                }

                $errors[] = [
                    'message' => $message,
                ];
            }

            foreach ($request->formErrors as $errorModel) {
                if (!isset($errorModel['message'])) continue;

                $errors[] = [
                    'message' => $errorModel['message'],
                ];
            }

            return $errors;
        });

        $page->content->isUserAuthenticated = (bool)$request->user;

        $page->steps = [
            ['name' => 'Получатель', 'isPassive' => true, 'isActive' => false, 'url' => $router->getUrlByRoute(new Routing\Order\Index(), ['shopId' => $request->shopId])],
            ['name' => 'Самовывоз и доставка', 'isPassive' => true, 'isActive' => true],
            ['name' => 'Оплата', 'isPassive' => false, 'isActive' => false],
        ];

        // шаблоны mustache
        $this->getTemplateRepository()->setListForPage($page, [
            // модальное окно с точками самовывоза
            [
                'id'       => 'tpl-order-delivery-point-popup',
                'name'     => 'page/order/delivery/point-popup',
                'partials' => [
                    'page/order/delivery/point-list',
                ],
            ],
            // календарь
            [
                'id'       => 'tpl-order-delivery-calendar',
                'name'     => 'page/order/delivery/calendar',
                'partials' => [],
            ],
            // всплываюшка для маркера
            [
                'id'       => 'tpl-order-delivery-marker-balloon',
                'name'     => 'page/order/delivery/marker-balloon',
                'partials' => [],
            ],
            // модальное окно для выбора адреса доставки
            [
                'id'       => 'tpl-order-delivery-address-popup',
                'name'     => 'page/order/delivery/address-popup',
                'partials' => [],
            ],
            // всплываюшка для скидок
            [
                'id'       => 'tpl-order-delivery-discount-popup',
                'name'     => 'page/order/delivery/discount-popup',
                'partials' => [],
            ],
            // подсказка для поиска точек самовывоза
            [
                'id'       => 'tpl-order-delivery-point-suggest',
                'name'     => 'page/order/delivery/point-suggest',
                'partials' => [],
            ],
        ]);
    }
}