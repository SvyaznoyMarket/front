<?php

namespace EnterMobile\Repository\Page\Order;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterAggregator\DateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Order\Complete as Page;

class Complete {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait, PriceHelperTrait, DateHelperTrait, TranslateHelperTrait;

    /**
     * @param Page $page
     * @param Complete\Request $request
     */
    public function buildObjectByRequest(Page $page, Complete\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();
        $priceHelper = $this->getPriceHelper();
        $dateHelper = $this->getDateHelper();
        $translateHelper = $this->getTranslateHelper();
        $pointRepository = new Repository\Partial\Point();

        $onlinePaymentMethodModelsById = $request->onlinePaymentMethodsById;

        $onlinePaymentMethodsById = [
            '5'  => [
                'id'          => '5',
                'name'        => 'Банковская карта',
                'images'      => ['visa2.png', 'mcard.png'],
                'smallImages' => ['Visa.png', 'master.png'],
            ],
            '8'  => [
                'id'          => '8',
                'name'        => 'Интернет-банк Промсвязьбанка',
                'images'      => ['psbfull.png'],
                'smallImages' => ['psb.png'],
            ],
            /*
            '13' => [
                'id'     => '13',
                'name'   => 'PayPal',
                'images' => ['paypal3.png'],
            ],
            '?1'  => [
                'id'     => '?1',
                'name'   => 'Яндекс.Деньги',
                'images' => ['yandexmoney.png'],
            ],
            */
        ];

        foreach ($request->orders as $orderModel) {
            /** @var \EnterModel\Order\Delivery|null $deliveryModel */
            $deliveryModel = isset($orderModel->deliveries[0]) ? $orderModel->deliveries[0] : null;

            $order = [
                'id'        => $orderModel->id,
                'number'    => $orderModel->number,
                'numberErp' => $orderModel->numberErp,
                'sum'       =>
                    $orderModel->sum
                    ? [
                        'name'  => $priceHelper->format($orderModel->sum),
                        'value' => $orderModel->sum,
                    ]
                    : null
                ,
                'delivery'  =>
                    $deliveryModel
                    ? call_user_func(function() use (&$deliveryModel, &$dateHelper) {
                        $date = null;
                        try {
                            $date = $deliveryModel->date ? (new \DateTime())->setTimestamp($deliveryModel->date) : null;
                        } catch (\Exception $e) {
                            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'critical']]);
                        }

                        $delivery = [
                            'type'     =>
                                $deliveryModel->type
                                ? [
                                    'name'  => $deliveryModel->type->shortName,
                                    'token' => $deliveryModel->type->token,
                                ]
                                : false
                            ,
                            'date'     =>
                                $date
                                ? [
                                    'name' => $date->format('d.m.Y'),
                                ]
                                : false
                            ,
                        ];

                        return $delivery;
                    })
                    : false
                ,
                'interval'  =>
                    $orderModel->interval
                    ? [
                        'from' => $orderModel->interval->from,
                        'to'   => $orderModel->interval->to,
                    ]
                    : false
                ,
                'address'   => !$orderModel->point ? $orderModel->address : false,
                'point'     => call_user_func(function() use (&$orderModel, &$pointRepository) {
                    if (!$pointModel = $orderModel->point) {
                        return false;
                    }

                    $point = [
                        'group'   => [
                            'name'  => $pointRepository->getGroupNameByType($pointModel->type),
                            'value' => $pointModel->type,
                        ],
                        'icon'    => $pointRepository->getIconByType($pointModel->type),
                        'address' => $pointModel->address,
                        'subway'  =>
                            $pointModel->subway
                            ? [
                                'name' => $pointModel->subway->name,
                                'line' =>
                                    $pointModel->subway->line
                                    ? [
                                        'color' => $pointModel->subway->line->color,
                                    ]
                                    : false
                                ,
                            ]
                            : false
                        ,
                    ];

                    return $point;
                }),
                'products'  => call_user_func(function() use (&$orderModel) {
                    $products = [];

                    $i = 0;
                    foreach ($orderModel->product as $productModel) {
                        $i++;
                        $products[] = [
                            'id'       => $productModel->id,
                            'quantity' => $productModel->quantity,
                            'sum'      => $productModel->sum,
                            'article'  => $productModel->article,
                            'name'     => $productModel->name,
                            'link'     => $productModel->link,
                            'isHidden' => $i > 2,
                        ];
                    }

                    return $products;
                }),
                'productMoreLink' => call_user_func(function() use (&$orderModel, &$translateHelper) {
                    $count = count($orderModel->product);

                    if ($count <= 2) {
                        return false;
                    }

                    $restCount = $count - 2;

                    return [
                        'name' => 'и ещё ' . $restCount . ' ' . $translateHelper->numberChoice($restCount, ['товар', 'товара', 'товаров']),
                    ];
                }),
                'isPrepayment' => call_user_func(function() use (&$config, &$orderModel) {
                    return
                        $config->order->prepayment->enabled
                        && ($orderModel->sum >= $config->order->prepayment->priceLimit)
                    ;
                }),
                'onlinePayment' => call_user_func(function() use (&$orderModel, &$onlinePaymentMethodModelsById, $onlinePaymentMethodsById) {
                    if (!count($onlinePaymentMethodModelsById) || !count($orderModel->paymentMethods)) {
                        return false;
                    }

                    $data = [
                        'images' => [],
                    ];
                    foreach ($onlinePaymentMethodModelsById as $paymentMethodModel) {
                        $paymentMethod = $onlinePaymentMethodsById[$paymentMethodModel->id];

                        $data['images'] = array_merge($data['images'], $paymentMethod['smallImages']);
                    }

                    return $data;
                }),
                'onlinePaymentJson' => json_encode(call_user_func(function() use (&$orderModel, &$onlinePaymentMethodModelsById, $onlinePaymentMethodsById, &$templateHelper, &$router) {
                    $paymentMethods = [];

                    foreach ($onlinePaymentMethodModelsById as $paymentMethodModel) {
                        $paymentMethods[] = $onlinePaymentMethodsById[$paymentMethodModel->id] + [
                            'dataValue' => $templateHelper->json([
                                'methodId' => $paymentMethodModel->id,
                                'orderId'  => $orderModel->id,
                            ]),
                        ];
                    }

                    return [
                        'paymentMethods' => $paymentMethods,
                        'order'          => [
                            'id' => $orderModel->id,
                        ],
                        'url'            => $router->getUrlByRoute(new Routing\Order\Payment\GetForm()),
                    ];
                }), JSON_UNESCAPED_UNICODE),
            ];

            $page->content->orders[] = $order;
        }
        $page->content->isSingleOrder = 1 === count($request->orders);

        if (!$request->isCompletePageReaded) {
            $page->content->dataGoogleAnalyticOrders = $templateHelper->json(array_map(function(\EnterModel\Order $orderModel) {
                return [
                    'number' => $orderModel->numberErp,
                    'isPartner' => $orderModel->isPartner,
                    'paySum' => $orderModel->paySum,
                    'delivery' => [
                        'price' => isset($orderModel->deliveries[0]) ? $orderModel->deliveries[0]->price : '',
                    ],
                    'region' => [
                        'name' => $orderModel->region->name,
                    ],
                    'products' => array_map(function (\EnterModel\Order\Product $product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'article' => $product->article,
                            'categories' => $product->category ? call_user_func($self = function (\EnterModel\Product\Category $category) use (&$self) {
                                return array_merge($category->parent ? $self($category->parent) : [], [['name' => $category->name]]);
                            }, $product->category) : [],
                            'price' => $product->price,
                            'quantity' => $product->quantity,
                        ];
                    }, $orderModel->product),
                ];
            }, $request->orders));
        }

        // заголовок
        $page->title = 'Оформление заказа - Завершение - Enter';

        $page->dataModule = 'order-complete';

        $page->steps = [
            ['name' => 'Получатель', 'isPassive' => false, 'isActive' => false],
            ['name' => 'Самовывоз и доставка', 'isPassive' => false, 'isActive' => false],
            ['name' => 'Оплата', 'isPassive' => false, 'isActive' => true],
        ];

        // шаблоны mustache
        (new Repository\Template())->setListForPage($page, [
            // модальное окно для выбора онлайн оплат
            [
                'id'       => 'tpl-order-complete-onlinePayment-popup',
                'name'     => 'page/order/complete/onlinePayment-popup',
                'partials' => [],
            ],
        ]);
    }
}