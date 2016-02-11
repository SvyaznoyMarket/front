<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;

    class ProductCardV2 {
        use ProductListingTrait, SessionTrait, CurlTrait;
        
        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $session = $this->getSession();
            $helper = new \Enter\Helper\Template();
            $productRepository = new \EnterMobileApplication\Repository\Product();
            $pointRepository = new \EnterRepository\Point();
            
            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            // ид товара
            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId', Http\Response::STATUS_BAD_REQUEST);
            }

            $returnReviews = (bool)$request->query['returnReviews'];
            $returnSimilarRelations = (bool)$request->query['returnSimilarRelations'];
            $returnAlsoBoughtRelations = (bool)$request->query['returnAlsoBoughtRelations'];
            $returnUser = (bool)$request->query['returnUser'];

            // контроллер
            $controller = new \EnterAggregator\Controller\ProductCard();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->config->mainMenu = false;
            $controllerRequest->config->favourite = true;
            $controllerRequest->config->review = $returnReviews;
            $controllerRequest->regionId = $regionId;
            $controllerRequest->productCriteria = ['id' => $productId];
            $controllerRequest->userToken = $userAuthToken;
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            // товар
            if (!$controllerResponse->product) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Товар #%s не найден', $productId));
            }

            $productRepository->setViewedProductIdToSession($controllerResponse->product->id, $session);

            // MAPI-76 Получение данных в едином формате
            call_user_func(function() use(&$controllerResponse) {
                if ($controllerResponse->product->model) {
                    /** @var Model\Product\Property[] $propertiesById */
                    $propertiesById = [];
                    foreach ($controllerResponse->product->properties as $property) {
                        $propertiesById[$property->id] = $property;
                    }

                    foreach ($controllerResponse->product->model->properties as $modelProperty) {
                        if (isset($propertiesById[$modelProperty->id])) {
                            $property = $propertiesById[$modelProperty->id];
                            foreach ($modelProperty->options as $modelOption) {
                                foreach ($property->options as $option) {
                                    if (preg_replace('/^(\d+)\.(\d+)$/', '$1,$2', $modelOption->value) === $option->value) {
                                        $modelOption->value = $option->value;
                                        $modelOption->shownValue = $option->value;
                                        break (3);
                                    }
                                }
                            }
                        }
                    }
                }
            });

            $recommendations = [
                'similar' => [],
                'alsoBought' => [],
            ];

            // Получение рекомендаций
            call_user_func(function() use(&$recommendations, $returnSimilarRelations, $returnAlsoBoughtRelations, $regionId, $controllerResponse) {
                if (!$returnSimilarRelations && !$returnAlsoBoughtRelations) {
                    return;
                }

                $controller = new \EnterAggregator\Controller\Product\RecommendedListByProduct();
                $controllerRequest = $controller->createRequest();
                $controllerRequest->config->alsoBought = $returnAlsoBoughtRelations;
                $controllerRequest->config->alsoViewed = false;
                $controllerRequest->config->similar = $returnSimilarRelations;
                $controllerRequest->regionId = $regionId;
                $controllerRequest->productIds = [$controllerResponse->product->id];

                $controllerResponse = $controller->execute($controllerRequest);

                if ($returnSimilarRelations) {
                    foreach (array_slice($controllerResponse->similarIdList, 0, 24) as $iProductId) {
                        if (empty($controllerResponse->recommendedProductsById[$iProductId])) {
                            continue;
                        }

                        $product = $controllerResponse->recommendedProductsById[$iProductId];
                        $product->sender = ['name' => 'retailrocket'];
                        $recommendations['similar'][] = $product;
                    }
                }

                if ($returnAlsoBoughtRelations) {
                    foreach (array_slice($controllerResponse->alsoBoughtIdList, 0, 24) as $i => $iProductId) {
                        if (empty($controllerResponse->recommendedProductsById[$iProductId])) {
                            continue;
                        }

                        $product = $controllerResponse->recommendedProductsById[$iProductId];
                        $product->sender = ['name' => 'retailrocket'];
                        $recommendations['alsoBought'][] = $product;
                    }
                }
            });

            $shopStatePointGroups = [];
            $shopStatePointsByUi = [];
            call_user_func(function() use(&$shopStatePointsByUi, &$shopStatePointGroups, $controllerResponse) {
                if (!$controllerResponse->product->shopStates) {
                    return;
                }

                $pointUis = [];
                foreach ($controllerResponse->product->shopStates as $shopState) {
                    $pointUis[] = $shopState->shop->ui;
                }

                if ($pointUis) {
                    $curl = $this->getCurl();
                    $pointListQuery = new Query\Point\GetListFromScms(null, $pointUis);
                    $curl->prepare($pointListQuery);
                    $curl->execute();

                    $result = $pointListQuery->getResult();
                    $shopStatePointGroups = $result['partners'];
                    foreach ($result['points'] as $point) {
                        $shopStatePointsByUi[$point['uid']] = $point;
                    }
                }
            });

            $response = [
                'product' => [
                    'id' => $controllerResponse->product->id,
                    'ui' => $controllerResponse->product->ui,
                    'article' => $controllerResponse->product->article,
                    'barcode' => $controllerResponse->product->barcode,
                    'typeId' => $controllerResponse->product->typeId,
                    'webName' => $helper->unescape($controllerResponse->product->webName),
                    'namePrefix' => $helper->unescape($controllerResponse->product->namePrefix),
                    'name' => $helper->unescape($controllerResponse->product->name),
                    'token' => $controllerResponse->product->token,
                    'link' => $controllerResponse->product->link,
                    'description' => $controllerResponse->product->description,
                    'tagline' => $controllerResponse->product->tagline,
                    'isBuyable' => $controllerResponse->product->isBuyable,
                    'isInShopOnly' => $controllerResponse->product->isInShopOnly,
                    'isInShopStockOnly' => $controllerResponse->product->isInShopStockOnly,
                    'isInShopShowroomOnly' => $controllerResponse->product->isInShopShowroomOnly,
                    'isInWarehouse' => $controllerResponse->product->isInWarehouse,
                    'isKitLocked' => $controllerResponse->product->isKitLocked,
                    'kitCount' => $controllerResponse->product->kitCount,
                    'category' => [
                        'id' => $controllerResponse->product->category->id,
                    ],
                    'brand' => $controllerResponse->product->brand ? [
                        'id'   => $controllerResponse->product->brand->id,
                        'name' => $controllerResponse->product->brand->name,
                    ] : null,
                    'properties' => $controllerResponse->product->properties,
                    'propertyGroups' => $controllerResponse->product->propertyGroups,
                    'pointStates' => array_filter(array_map(function(\EnterModel\Product\ShopState $shopState) use($shopStatePointsByUi) {
                        if (!$shopState->shop || !$shopStatePointsByUi[$shopState->shop->ui]) {
                            return null;
                        }

                        return [
                            'point' => call_user_func(function() use($shopState, $shopStatePointsByUi) {
                                $point = $shopStatePointsByUi[$shopState->shop->ui];
                                return [
                                    'group' => ['id' => $point['partner']],
                                    'id' => $shopState->shop->id,
                                    'ui' => $point['uid'],
                                    'address' => $point['address'],
                                    'regime' => $point['working_time'],
                                    'longitude' => isset($point['location'][0]) ? $point['location'][0] : null,
                                    'latitude' => isset($point['location'][1]) ? $point['location'][1] : null,
                                    'subway' => [[
                                        'name' => isset($point['subway']['name']) ? $point['subway']['name'] : null,
                                        'line' => [
                                            'name' => isset($point['subway']['line_name']) ? $point['subway']['line_name'] : null,
                                            'color' => isset($point['subway']['line_color']) ? $point['subway']['line_color'] : null,
                                        ],
                                    ]],
                                ];
                            }),
                            'quantity' => $shopState->quantity,
                            'showroomQuantity' => $shopState->showroomQuantity,
                            'isInShowroomOnly' => $shopState->isInShowroomOnly,
                        ];
                    }, $controllerResponse->product->shopStates)),
                    'pointGroups' => array_map(function($group) use(&$pointRepository) {
                        return [
                            'id' => $group['slug'],
                            'name' => $group['name'],
                            'media' => $pointRepository->getMedia($group['slug'], ['logo', 'marker']),
                        ];
                    }, $shopStatePointGroups),
                    'price' => $controllerResponse->product->price,
                    'oldPrice' => $controllerResponse->product->oldPrice,
                    'labels' => array_map(function(Model\Product\Label $label) {
                        return [
                            'id'    => $label->id,
                            'name'  => $label->name,
                            'media' => $label->media,
                        ];
                    }, $controllerResponse->product->labels),
                    'prepayment' => $controllerResponse->product->prepayment ? [
                        'message' => $controllerResponse->product->prepayment->message,
                        'contentId' => $controllerResponse->product->prepayment->contentId,
                    ] : null,
                    'media' => $productRepository->getMedia($controllerResponse->product),
                    'model' => $controllerResponse->product->model ? [
                        'properties' => $controllerResponse->product->model->property ? [
                            [
                                'id' => $controllerResponse->product->model->property->id,
                                'name' => $controllerResponse->product->model->property->name,
                                'unit' => '',
                                'isImage' => false,
                                'options' => array_map(function(\EnterModel\Product\ProductModel\Property\Option $option) {
                                    return [
                                        'value' => $option->value,
                                        'product' => $option->product ? [
                                            'id' => $option->product->id,
                                            'name' => $option->product->name,
                                            'link' => $option->product->link,
                                            'token' => $option->product->token,
                                            'image' => '',
                                        ] : null,
                                        'shownValue' => $option->value,
                                    ];
                                }, $controllerResponse->product->model->property->options),
                            ]
                        ] : [],
                    ] : null,
                    'line' => $controllerResponse->product->line,
                    'nearestDeliveries' => call_user_func(function() use($controllerResponse) {
                        if (!is_array($controllerResponse->product->nearestDeliveries)) {
                            return [];
                        }

                        $result = [];
                        // Объединяем блоки self* в один блок self (MAPI-92, MAPI-101)
                        foreach ($controllerResponse->product->nearestDeliveries as $nearestDelivery) {
                            $token = $nearestDelivery->token === 'standart' ? 'standart' : 'self';

                            if (isset($result[$token])) {
                                $result[$token]['price']['from'] = min($result[$token]['price']['from'], (float)$nearestDelivery->price);
                                $result[$token]['price']['to'] = max($result[$token]['price']['to'], (float)$nearestDelivery->price);

                                if ($nearestDelivery->deliveredAt) {
                                    if ($result[$token]['deliveredAt']) {
                                        $result[$token]['deliveredAt']['from'] = min($result[$token]['deliveredAt']['from'], $nearestDelivery->deliveredAt);
                                        $result[$token]['deliveredAt']['to'] = max($result[$token]['deliveredAt']['to'], $nearestDelivery->deliveredAt);
                                    } else {
                                        $result[$token]['deliveredAt'] = [
                                            'from' => $nearestDelivery->deliveredAt,
                                            'to' => $nearestDelivery->deliveredAt,
                                        ];
                                    }
                                }
                            } else {
                                $result[$token] = [
                                    'id' => $token === 'standart' ? 1 : 3,
                                    'token' => $token,
                                    'productId' => $nearestDelivery->productId,
                                    'price' => [
                                        'from' => (float)$nearestDelivery->price,
                                        'to' => (float)$nearestDelivery->price,
                                    ],
                                    'deliveredAt' => $nearestDelivery->deliveredAt ? [
                                        'from' => $nearestDelivery->deliveredAt,
                                        'to' => $nearestDelivery->deliveredAt,
                                    ] : null,
                                ];
                            }
                        }

                        // Заполняем price.single и deliveredAt.single, обнуляя при необходимости *.from и *.to
                        foreach ($result as $key => $item) {
                            if ($item['price']['from'] == $item['price']['to']) {
                                $result[$key]['price']['single'] = $result[$key]['price']['from'];
                                $result[$key]['price']['from'] = null;
                                $result[$key]['price']['to'] = null;
                            } else {
                                $result[$key]['price']['single'] = null;
                            }

                            if ($item['deliveredAt']) {
                                if ($item['deliveredAt']['from']->getTimestamp() == $item['deliveredAt']['to']->getTimestamp()) {
                                    $result[$key]['deliveredAt']['single'] = $result[$key]['deliveredAt']['from'];
                                    $result[$key]['deliveredAt']['from'] = null;
                                    $result[$key]['deliveredAt']['to'] = null;
                                } else {
                                    $result[$key]['deliveredAt']['single'] = null;
                                }
                            }
                        }

                        return array_values($result);
                    }),
                    'accessoryIds' => $controllerResponse->product->accessoryIds,
                    'relatedIds' => $controllerResponse->product->relatedIds,
                    'relation' => [
                        'accessories' => $this->getProductList($controllerResponse->product->relation->accessories),
                        'similar' => $this->getProductList($recommendations['similar']),
                        'alsoBought' => $this->getProductList($recommendations['alsoBought']),
                    ],
                    'kit' => $controllerResponse->product->kit,
                    'rating' => $controllerResponse->product->rating ? [
                        'score'       => $controllerResponse->product->rating->score,
                        'starScore'   => $controllerResponse->product->rating->starScore,
                        'reviewCount' => $controllerResponse->product->rating->reviewCount,
                    ] : null,
                    'reviews' => array_map(function(\EnterModel\Product\Review $review) {
                        return [
                            'score'     => $review->score,
                            'starScore' => $review->starScore,
                            'extract'   => $review->extract,
                            'pros'      => $review->pros,
                            'cons'      => $review->cons,
                            'author'    => $review->author,
                            'source'    => $review->source,
                            'createdAt' => $review->createdAt ? $review->createdAt->getTimestamp() : null,
                        ];
                    }, $controllerResponse->product->reviews),
                    'trustfactors' => $controllerResponse->product->trustfactors,
                    'partnerOffers' => $productRepository->getPartnerOffers($controllerResponse->product),
                    'availableStoreQuantity' => $controllerResponse->product->availableStoreQuantity,
                    'favorite' => $controllerResponse->product->favorite,
                    'sender' => $controllerResponse->product->sender,
                    'ga' => $controllerResponse->product->ga,
                    'isStore' => $controllerResponse->product->isStore,
                    'storeLabel' => $controllerResponse->product->storeLabel,
                ],
                'user' => $returnUser ? $controllerResponse->user : [],
            ];

            return new Http\JsonResponse($response);
        }
    }
}