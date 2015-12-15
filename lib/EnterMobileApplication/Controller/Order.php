<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterRepository as Repository;
    use EnterQuery as Query;
    use EnterModel as Model;

    class Order {
        use ConfigTrait, CurlTrait, LoggerTrait, ProductListingTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();
            $config = $this->getConfig();
            $productRepository = new \EnterMobileApplication\Repository\Product();
            $pointRepository = new \EnterMobileApplication\Repository\Point();
            $helper = new \Enter\Helper\Template();

            // токен для получения заказа
            $accessToken = is_string($request->query['accessToken']) ? $request->query['accessToken'] : null;
            if (!$accessToken) {
                throw new \Exception('Не передан accessToken', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос заказа
            $itemQuery = new Query\Order\GetItemByAccessToken($accessToken);
            $itemQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($itemQuery);

            $curl->execute();

            // заказ
            $order = (new Repository\Order())->getObjectByQuery($itemQuery);
            if (!$order) {
                throw new \Exception('Заказ не найден', 404);
            }

            $point = null;
            try {
                $paymentListQuery = new Query\PaymentMethod\GetListByOrderNumberErp($order->numberErp, $order->regionId);
                $curl->prepare($paymentListQuery);

                $pointItemQuery = null;
                if ($order->point && $order->point->ui) {
                    $pointItemQuery = new Query\Point\GetItemByUi($order->point->ui);
                    $curl->prepare($pointItemQuery);
                }

                $curl->execute();

                $order->paymentMethods = array_values((new Repository\PaymentMethod())->getIndexedObjectListByQuery($paymentListQuery));
                foreach ($order->paymentMethods as $paymentMethod) {
                    /** @var Model\PaymentMethod $paymentMethod */
                    // MAPI-179
                    if (!$paymentMethod->sum) {
                        $paymentMethod->sum = $order->paySum;
                    }
                }

                if ($pointItemQuery) {
                    $point = $pointItemQuery->getResult();
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $orderProductsById = [];
            foreach ((array)$order->product as $orderProduct) {
                $orderProductsById[$orderProduct->id] = $orderProduct;
            }

            $productListQuery = null;
            $descriptionListQuery = null;
            if ((bool)$orderProductsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($orderProductsById), $order->regionId, ['related' => false]);
                $curl->prepare($productListQuery);
                
                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(array_keys($orderProductsById), [
                    'media'       => true,
                    'media_types' => ['main'], // только главная картинка
                    'category'    => true,
                    'label'       => true,
                    'brand'       => true,
                ]);
                $curl->prepare($descriptionListQuery);
            }

            $curl->execute();

            // товары сгруппированные по id
            $productsById = [];
            try {
                $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery], [$descriptionListQuery]) : [];
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            }

            $media = $pointRepository->getMedia($point['partner']['slug'], ['logo']);
            $imageUrl = null;
            foreach ($media->photos as $media) {
                if (in_array('logo', $media->tags, true)) {
                    foreach ($media->sources as $source) {
                        if ($source->type === '100x100') {
                            $imageUrl = $source->url;
                            break(2);
                        }
                    }
                }
            }
            
            $response = ['order' => [
                'id' => $order->id,
                'number' => $order->number,
                'numberErp' => $order->numberErp,
                'token' => $order->token,
                'status' => $order->status ? [
                    'id' => $order->status->id,
                    'name' => $order->status->name,
                ] : null,
                'paymentStatus' => $order->paymentStatus ? [
                    'id' => $order->paymentStatus->id,
                    'name' => $order->paymentStatus->name,
                ] : null,
                'paymentMethods' => call_user_func(function() use($order) {
                    $paymentMethods = [];
                    foreach ($order->paymentMethods as $paymentMethod) {
                        if (!$paymentMethod->isOnline) {
                            continue;
                        }

                        $paymentMethods[] = [
                            'id' => (string)$paymentMethod->id,
                            'ui' => (string)$paymentMethod->ui,
                            'name' => (string)$paymentMethod->name,
                            'description' => (string)$paymentMethod->description,
                            'isCredit' => (bool)$paymentMethod->isCredit,
                            'isOnline' => (bool)$paymentMethod->isOnline,
                            'isCorporative' => (bool)$paymentMethod->isCorporative,
                            'groupId' => (string)$paymentMethod->groupId,
                            'group' => $paymentMethod->group ? [
                                'id' => (string)$paymentMethod->group->id,
                                'name' => (string)$paymentMethod->group->name,
                                'description' => (string)$paymentMethod->group->description,
                            ] : null,
                            'media' => $paymentMethod->media,
                            'sum' => $paymentMethod->sum,
                            'discount' => $paymentMethod->discount ? [
                                'value' => $paymentMethod->discount->value,
                                'unit' => $paymentMethod->discount->unit === 'rub' ? 'руб.' : $paymentMethod->discount->unit,
                            ] : null,
                        ];
                    }

                    return $paymentMethods;
                }),
                'sum' => $order->sum,
                'address' => $order->address,
                'createdAt' => $order->createdAt,
                'updatedAt' => $order->updatedAt,
                'product' => array_map(function(Model\Order\Product $orderProduct) use(&$productsById, $helper, $productRepository) {
                    $product = isset($productsById[$orderProduct->id]) ? $productsById[$orderProduct->id] : new Model\Product();

                    return [
                        'id'                   => $orderProduct->id,
                        'price'                => $orderProduct->price,
                        'quantity'             => $orderProduct->quantity,
                        'sum'                  => $orderProduct->sum,
                        'article'              => $product->article,
                        'webName'              => $helper->unescape($product->webName),
                        'namePrefix'           => $helper->unescape($product->namePrefix),
                        'name'                 => $helper->unescape($product->name),
                        'isBuyable'            => $product->isBuyable,
                        'isInShopOnly'         => $product->isInShopOnly,
                        'isInShopStockOnly'    => $product->isInShopStockOnly,
                        'isInShopShowroomOnly' => $product->isInShopShowroomOnly,
                        'brand'                => $product->brand ? [
                            'id'   => $product->brand->id,
                            'name' => $product->brand->name,
                        ] : null,
                        'oldPrice'             => $product->oldPrice,
                        'labels'               => array_map(function(Model\Product\Label $label) {
                            return [
                                'id'    => $label->id,
                                'name'  => $label->name,
                                'media' => $label->media,
                            ];
                        }, $product->labels),
                        'media'           => $product->media,
                        'rating'          => $product->rating ? [
                            'score'       => $product->rating->score,
                            'starScore'   => $product->rating->starScore,
                            'reviewCount' => $product->rating->reviewCount,
                        ] : null,
                        'favorite'        => isset($product->favorite) ? $product->favorite : null,
                        'partnerOffers'   => $productRepository->getPartnerOffers($product),
                        'storeLabel'      => $product->storeLabel,
                    ];
                }, $order->product),
                'oldPaySum' => $order->paySumWithOnlineDiscount ? $order->paySum : null,
                'paySum' => $order->paySumWithOnlineDiscount ? $order->paySumWithOnlineDiscount : $order->paySum,
                'discountSum' => $order->discountSum,
                'isCancelAvailable' => $order->isCancelAvailable,
                'subwayId' => $order->subwayId,
                'deliveries' => $order->deliveries,
                'deliveryType' => $order->deliveryType,
                'interval' => $order->interval,
                'shopId' => $point['partner']['slug'] === 'enter' ? $order->shopId : null, // TODO перенести в point.id
                'point' => $point ? [
                    'ui' => $point['uid'],
                    'name' => $pointRepository->getName($point['partner']['slug'], $point['partner']['name']),
                    'media' => $media,
                    'imageUrl' => $imageUrl, // TODO MAPI-61 Удалить элементы pointGroups.<int>.imageUrl и pointGroups.<int>.markerUrl из ответа метода Cart/Split и point.imageUrl из ответа метода Order
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
                ] : null,
            ]];

            return new Http\JsonResponse($response);
        }
    }
}