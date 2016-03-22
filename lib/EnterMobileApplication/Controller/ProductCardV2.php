<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\TranslateHelperTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;

    class ProductCardV2 {
        use ProductListingTrait, SessionTrait, CurlTrait, LoggerTrait, TranslateHelperTrait;

        /**
         * @SWG\Get(
         *     path="/ProductCardV2",
         *     summary="Возвращает информацию о товаре",
         *     @SWG\Parameter(name="clientId",                  type="string",                  in="query", required=true,               description=""),
         *     @SWG\Parameter(name="regionId",                  type="string",                  in="query", required=true,               description=""),
         *     @SWG\Parameter(name="token",                     type="string",                  in="query", required=true,               description=""),
         *     @SWG\Parameter(name="token",                     type="string",                  in="query", required=true,               description="токен аутентификации пользователя"),
         *     @SWG\Parameter(name="productId",                 type="string",                  in="query", required=true,               description="id товара"),
         *     @SWG\Parameter(name="returnReviews",             type="string", enum={"0", "1"}, in="query", required=false, default="0", description="возвращать ли содержимое элемента product.reviews"),
         *     @SWG\Parameter(name="returnSimilarRelations",    type="string", enum={"0", "1"}, in="query", required=false, default="0", description="возвращать ли содержимое элемента product.relation.alsoBought"),
         *     @SWG\Parameter(name="returnAlsoBoughtRelations", type="string", enum={"0", "1"}, in="query", required=false, default="0", description="возвращать ли содержимое элемента product.relation.similar"),
         *     @SWG\Parameter(name="returnUser",                type="string", enum={"0", "1"}, in="query", required=false, default="0", description="возвращать ли содержимое элемента user"),
         *     @SWG\Response(
         *         response="200",
         *         description="",
         *         @SWG\Schema(
         *             @SWG\Property(property="product", type="object", properties={@SWG\Property(property="id", type="string")}
         *             )
         *         )
         *     ),
         *     @SWG\Response(
         *         description="",
         *         response="500"
         *     )
         * )
         *
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
                    'description' => $helper->unescape(strip_tags($controllerResponse->product->description)),
                    'tagline' => $helper->unescape(strip_tags($controllerResponse->product->tagline)),
                    'isBuyable' => $controllerResponse->product->isBuyable,
                    'isInShopOnly' => $controllerResponse->product->isInShopOnly,
                    'isInShopStockOnly' => $controllerResponse->product->isInShopStockOnly,
                    'isInShopShowroomOnly' => $controllerResponse->product->isInShopShowroomOnly,
                    'isInWarehouse' => $controllerResponse->product->isInWarehouse,
                    'isKit' => (bool)$controllerResponse->product->relation->kits,
                    'isKitLocked' => (bool)$controllerResponse->product->isKitLocked,
                    'kitProducts' => $this->getProductList($controllerResponse->product->relation->kits, false, true),
                    'kitCount' => $controllerResponse->product->kitCount, // deprecated
                    'kit' => $controllerResponse->product->kit, // deprecated
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
                                'options' => array_map(function(\EnterModel\Product\ProductModel\Property\Option $option) use($controllerResponse) {
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
                                        'isSelected' => $option->product && $controllerResponse->product && $option->product->id === $controllerResponse->product->id,
                                    ];
                                }, $controllerResponse->product->model->property->options),
                            ]
                        ] : [],
                    ] : null,
                    'line' => $controllerResponse->product->line,
                    'nearestDeliveries' => call_user_func(function() use($controllerResponse, $productRepository) {
                        if (!is_array($controllerResponse->product->deliveries)) {
                            return [];
                        }

                        $result = [];
                        foreach ($controllerResponse->product->deliveries as $delivery) {
                            // Объединяем блоки self* в один блок self (MAPI-92, MAPI-101)
                            $token = $delivery->isPickup ? 'self' : 'standart';

                            if (isset($result[$token])) {
                                $result[$token]['price']['from'] = min($result[$token]['price']['from'], (float)$delivery->price);
                                $result[$token]['price']['to'] = max($result[$token]['price']['to'], (float)$delivery->price);

                                if ($delivery->nearestDeliveredAt) {
                                    if ($result[$token]['deliveredAt']) {
                                        $result[$token]['deliveredAt']['from'] = min($result[$token]['deliveredAt']['from'], $delivery->nearestDeliveredAt);
                                        $result[$token]['deliveredAt']['to'] = max($result[$token]['deliveredAt']['to'], $delivery->nearestDeliveredAt);
                                    } else {
                                        $result[$token]['deliveredAt'] = [
                                            'from' => $delivery->nearestDeliveredAt,
                                            'to' => $delivery->nearestDeliveredAt,
                                        ];
                                    }
                                }
                            } else {
                                $result[$token] = [
                                    // Объединяем блоки self* в один блок self (MAPI-92, MAPI-101)
                                    'id' => $token === 'standart' ? 1 : 3,
                                    'token' => $token,
                                    'productId' => $delivery->productId,
                                    'price' => [
                                        'from' => (float)$delivery->price,
                                        'to' => (float)$delivery->price,
                                    ],
                                    'deliveredAt' => $delivery->nearestDeliveredAt ? [
                                        'from' => $delivery->nearestDeliveredAt,
                                        'to' => $delivery->nearestDeliveredAt,
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

                        try {
                            // Заполняем deliveredAt.text
                            foreach ($result as $token => $item) {
                                $result[$token]['deliveredAt']['text'] = '';

                                $deliveryWithMinDate = $productRepository->getDeliveriesWithMinDate($controllerResponse->product->deliveries, $token === 'self' ? true : false);
                                if ($deliveryWithMinDate) {
                                    if ($deliveryWithMinDate->dateInterval && $token === 'self') {
                                        $result[$token]['deliveredAt']['text'] = '';
                                        if ($deliveryWithMinDate->dateInterval->from) {
                                            $result[$token]['deliveredAt']['text'] .= 'с ' . $deliveryWithMinDate->dateInterval->from->format('d.m');
                                        }

                                        if ($deliveryWithMinDate->dateInterval->to) {
                                            $result[$token]['deliveredAt']['text'] .= ' по ' . $deliveryWithMinDate->dateInterval->to->format('d.m');
                                        }
                                        
                                        $result[$token]['deliveredAt']['text'] = trim($result[$token]['deliveredAt']['text']);
                                    } else if (!empty($deliveryWithMinDate->dates[0]) && !$deliveryWithMinDate->dateInterval && $deliveryWithMinDate->dates[0] && $dayFrom = $deliveryWithMinDate->dates[0]->diff((new \DateTime())->setTime(0, 0, 0))->days) {
                                        $dayRangeFrom = $dayFrom > 1 ? $dayFrom - 1 : $dayFrom;
                                        $dayRangeTo = $dayRangeFrom + 2;

                                        $result[$token]['deliveredAt']['text'] = $dayRangeFrom . '-' . $dayRangeTo . ' ' . $this->getTranslateHelper()->numberChoice($dayRangeTo, ['день', 'дня', 'дней']);
                                    } else if (!empty($deliveryWithMinDate->dates[0])) {
                                        $result[$token]['deliveredAt']['text'] = mb_strtolower($this->getTranslateHelper()->humanizeDate2($deliveryWithMinDate->dates[0]));
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['delivery']]);
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