<?php

namespace EnterMobile\Repository\Page\Order;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterAggregator\DateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Order\Delivery as Page;

class Delivery {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait, PriceHelperTrait, TranslateHelperTrait, DateHelperTrait;

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

        // заголовок
        $page->title = 'Оформление заказа - Способ получения - Enter';

        $page->dataModule = 'order';

        $page->content->region = [
            'name' => $request->region->name,
        ];

        $page->content->deliveryForm['url'] = $router->getUrlByRoute(new Routing\Order\Delivery());

        $page->content->form->url = $router->getUrlByRoute(new Routing\Order\Create());
        $page->content->form->errorDataValue = $templateHelper->json($request->formErrors);

        $splitModel = $request->split;

        // индексация токенов методов доставки по группам доставки
        $deliveryMethodTokensByGroupToken = [];
        foreach ($splitModel->deliveryMethods as $deliveryMethodModel) {
            $deliveryMethodTokensByGroupToken[$deliveryMethodModel->groupId][] = $deliveryMethodModel->token;
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
                        'url'  => $orderModel->seller->offerUrl,
                    ]
                    : false,
                'sum'            => [
                    'name'  => $priceHelper->format($orderModel->sum),
                    'value' => $orderModel->sum,
                ],
                'delivery'       => call_user_func(function() use (&$templateHelper, &$priceHelper, &$splitModel, &$orderModel, &$deliveryGroupModel, &$pointGroupByTokenIndex, &$pointByGroupAndIdIndex) {
                    $delivery = false;

                    if ($orderModel->delivery) {
                        // группа точки
                        $pointGroup = null;
                        // точка
                        $point = null;
                        // если выбрана точка получения заказа ...
                        if ($orderModel->delivery->point) {
                            $pointGroup =
                                isset($pointGroupByTokenIndex[$orderModel->delivery->point->groupToken])
                                ? $splitModel->pointGroups[
                                    $pointGroupByTokenIndex[$orderModel->delivery->point->groupToken]
                                ]
                                : null
                            ;
                            $point =
                                ($pointGroup && isset($pointByGroupAndIdIndex[$pointGroup->token][$orderModel->delivery->point->id]))
                                ? $pointGroup->points[
                                    $pointByGroupAndIdIndex[$orderModel->delivery->point->groupToken][$orderModel->delivery->point->id]
                                ]
                                : null
                            ;
                            if (!$point) {
                                $this->getLogger()->push(['type' => 'error', 'message' => 'Точка не найдена', 'pointId' => $orderModel->delivery->point->id, 'group' => $orderModel->delivery->point->groupToken, 'sender' => __FILE__ . ' ' . __LINE__, 'tag' => ['order.split', 'critical']]);
                            }
                        }

                        $delivery = [
                            'isStandart'  => 2 == $deliveryGroupModel->id,
                            'isSelf'      => 1 == $deliveryGroupModel->id,
                            'name'        => $deliveryGroupModel->name,
                            'price'       => [
                                'isCurrency' => $orderModel->delivery->price > 0,
                                'name'       => ($orderModel->delivery->price > 0) ? $priceHelper->format($orderModel->delivery->price) : 'Бесплатно',
                                'value'      => $orderModel->delivery->price,
                            ],
                            'point'       =>
                                $point
                                ? [
                                    'id'     => $point->id,
                                    'name'   => $point->name,
                                    'group'  => [
                                        'token' => $pointGroup->token,
                                        'name'  => $pointGroup->blockName,
                                    ],
                                    'icon'   => $this->getPointIcon($pointGroup->token),
                                    'subway' =>
                                        isset($point->subway[0])
                                            ? [
                                            'name'  => $point->subway[0]->name,
                                            'color' => isset($point->subway[0]->line) ? $point->subway[0]->line->color : false,
                                        ]
                                            : false
                                    ,
                                    'regime' => $point->regime,
                                    'order'  => [
                                        'id' => $orderModel->blockName,
                                    ],
                                ]
                                : false
                            ,
                            'isCompleted' =>
                                ((bool)$point && (1 == $deliveryGroupModel->id))
                                || (2 == $deliveryGroupModel->id)
                            ,
                            'interval'    =>
                                $orderModel->delivery->interval
                                ? [
                                    'from' => $orderModel->delivery->interval->from,
                                    'to'   => $orderModel->delivery->interval->to,
                                ]
                                : false
                            ,
                            'intervals'   => array_map(
                                function(\EnterModel\Cart\Split\Interval $interval) use (&$templateHelper, &$orderModel) {
                                    return [
                                        'from'      => $interval->from,
                                        'to'        => $interval->to,
                                        //'isActive'  => ($interval->from === $orderModel->delivery->interval->from) && ($interval->to === $orderModel->delivery->interval->to),
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
                                'name'  => $priceHelper->format($productModel->price),
                                'value' => $productModel->price,
                            ],
                            'sum'        => [
                                'name'  => $priceHelper->format($productModel->sum),
                                'value' => $productModel->sum,
                            ],
                            'url'        => $productModel->url,
                            'image'      =>
                                isset($productModel->media->photos[0])
                                ? (string)(new Routing\Product\Media\GetPhoto($productModel->media->photos[0], 'product_160'))
                                : null
                            ,
                        ];
                    }

                    return $products;
                }),
                'pointDataValue' => json_encode(call_user_func(function() use (&$templateHelper, &$priceHelper, &$dateHelper, &$splitModel, &$orderModel, &$pointGroupByTokenIndex, &$pointByGroupAndIdIndex) {
                    $points = [];
                    $filtersByToken = [
                        'type' => [],
                        'cost' => [],
                        'date' => [],
                    ];

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

                        // дата
                        $date = null;
                        try {
                            $date = $dateHelper->humanizeDate(
                                new \DateTime($possiblePointModel->nearestDay)
                            );
                        } catch (\Exception $e) {
                        }

                        $points[] = [
                            'id'        => $possiblePointModel->id,
                            'name'      => $point->name,
                            'group'     => $pointGroup->blockName,
                            'icon'      => $this->getPointIcon($pointGroup->token),
                            'date'      => $date ?: false,
                            'cost'      => $possiblePointModel->cost ?: false,
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
                            'dataValue'  => $templateHelper->json([ // FIXME - вынести в js
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

                        // фильтр по типу точки
                        if (!isset($filtersByToken['type'][$pointGroup->blockName])) {
                            $filtersByToken['type'][$pointGroup->blockName] = null;
                        }
                        // фильтр по цене
                        if (!isset($filtersByToken['cost'][$possiblePointModel->cost])) {
                            $filtersByToken['cost'][$possiblePointModel->cost] = null;
                        }
                        // фильтр по дате
                        if (!isset($filtersByToken['date'][$date])) {
                            $filtersByToken['date'][$date] = null;
                        }
                    }

                    // convert filter format
                    $filtersByToken = array_map(
                        function($filter) {
                            return array_keys($filter);
                        },
                        $filtersByToken
                    );

                    // cost filter fix
                    array_walk($filtersByToken['cost'], function(&$v) { if (!$v) $v = false; });

                    return [
                        'points'  => $points,
                        'filters' => $filtersByToken,
                    ];
                }), JSON_UNESCAPED_UNICODE),
                'messages'       => call_user_func(function() use (&$config, &$orderModel, &$priceHelper) {
                    $messages = [];

                    // предоплата
                    if (
                        $config->order->prepayment->enabled
                        && ($orderModel->sum >= $config->order->prepayment->priceLimit)
                    ) {
                        $messages[] = [
                            'cost'         => $priceHelper->format($config->order->prepayment->priceLimit),
                            'isPrepayment' => true,
                        ];
                    }

                    return $messages;
                }),
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

        // шаблоны mustache
        (new Repository\Template())->setListForPage($page, [
            [
                'id'       => 'tpl-order-delivery-point-popup',
                'name'     => 'page/order/delivery/point-popup',
                'partials' => [
                    'partial/cart/button',
                ],
            ],
        ]);
    }

    /**
     * @param string $groupToken
     * @return string
     */
    private function getPointIcon($groupToken) {
        $icon = null;

        switch ($groupToken) {
            case 'self_partner_pickpoint_pred_supplier':
            case 'self_partner_pickpoint':
                $icon = 'pickpoint';
                break;
            case 'self_partner_svyaznoy_pred_supplier':
            case 'self_partner_svyaznoy':
            case 'shops_svyaznoy':
                $icon = 'svyaznoy';
                break;
            default:
                $icon = 'enter';
        }

        return $icon;
    }
}