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
                'id'            => $orderModel->blockName,
                'name'          => sprintf('Заказ №%s', $i),
                'seller'        =>
                    $orderModel->seller
                    ? [
                        'name' => $orderModel->seller->name,
                        'url'  => $orderModel->seller->offerUrl,
                    ]
                    : false,
                'sum'          => [
                    'name'  => $priceHelper->format($orderModel->sum),
                    'value' => $orderModel->sum,
                ],
                'delivery'      =>
                    $orderModel->delivery
                    ? [
                        'isStandart' => 2 == $deliveryGroupModel->id,
                        'isSelf'     => 1 == $deliveryGroupModel->id,
                        'name'       => $deliveryGroupModel->name,
                        'price'      => [
                            'isCurrency' => $orderModel->delivery->price > 0,
                            'name'       => ($orderModel->delivery->price > 0) ? $priceHelper->format($orderModel->delivery->price) : 'Бесплатно',
                            'value'      => $orderModel->delivery->price,
                        ],
                        'point'      => $orderModel->delivery && $orderModel->delivery->point,
                    ]
                    : false
                ,
                'deliveries'    => call_user_func(function() use (&$templateHelper, &$priceHelper, &$splitModel, &$orderModel, &$deliveryMethodTokensByGroupToken) {
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
                                        ]
                                    ],
                                ],
                            ]),
                            'name'       => $deliveryGroupModel->name,
                            'isActive'   => $orderModel->delivery && in_array($orderModel->delivery->methodToken, $deliveryMethodTokensByGroupToken[$deliveryGroupModel->id]),
                        ];
                    }

                    return $deliveries;
                }),
                'products'    => call_user_func(function() use (&$templateHelper, &$priceHelper, &$splitModel, &$orderModel) {
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
                'pointDataValue' => $templateHelper->json(
                    call_user_func(function() use (&$templateHelper, &$priceHelper, &$dateHelper, &$splitModel, &$orderModel, &$pointGroupByTokenIndex, &$pointByGroupAndIdIndex) {
                        $points = [];

                        foreach ($orderModel->possiblePoints as $possiblePointModel) {
                            $pointGroupIndex = isset($pointGroupByTokenIndex[$possiblePointModel->groupToken]) ? $pointGroupByTokenIndex[$possiblePointModel->groupToken] : null;
                            $pointIndex = isset($pointByGroupAndIdIndex[$possiblePointModel->groupToken][$possiblePointModel->id]) ? $pointByGroupAndIdIndex[$possiblePointModel->groupToken][$possiblePointModel->id] : null;

                            $point = ($pointGroupIndex && $pointIndex) ? $splitModel->pointGroups[$pointGroupIndex]->points[$pointIndex] : null;
                            if (!$point) {
                                $this->getLogger()->push(['type' => 'warn', 'message' => 'Точка не найдена', 'pointId' => $possiblePointModel->id, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split', 'critical']]);
                                continue;
                            }

                            $date = null;
                            try {
                                $date = new \DateTime($possiblePointModel->nearestDay);
                            } catch (\Exception $e) {}

                            $points[] = [
                                'id'         => $possiblePointModel->id,
                                'name'       => $point->name,
                                'type'       => [
                                    'token' => $possiblePointModel->groupToken,
                                    'name'  => isset($splitModel->pointGroups),
                                ],
                                'date'       =>
                                    $date
                                        ? $dateHelper->humanizeDate($date)
                                        : false,
                                'cost'       => $possiblePointModel->cost ? $possiblePointModel->cost : false,
                                'subway'     =>
                                    isset($point->subway[0])
                                        ? [
                                        'name'  => $point->subway[0]->name,
                                        'color' => isset($point->subway[0]->line) ? $point->subway[0]->line->color : false,
                                    ]
                                        : false
                                ,
                                'regime'     => $point->regime,
                            ];
                        }

                        return $points;
                    })
                ),
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
}