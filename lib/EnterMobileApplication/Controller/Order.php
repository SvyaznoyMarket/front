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
                if ($order->point && $order->point->ui) {
                    $pointItemQuery = new Query\Point\GetItemByUi($order->point->ui);
                    $curl->prepare($pointItemQuery)->execute();
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
            if ((bool)$orderProductsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($orderProductsById), $order->regionId, ['related' => false]);
                $curl->prepare($productListQuery);
            }

            $curl->execute();

            // товары сгруппированные по id
            $productsById = [];
            try {
                $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            }

            $descriptionListQuery = null;
            if ($productsById) {
                $descriptionListQuery = new Query\Product\GetDescriptionListByUiList(
                    array_map(function(Model\Product $product) { return $product->ui; }, $productsById),
                    [
                        'media'       => true,
                        'media_types' => ['main'], // только главная картинка
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                    ]
                );
                $curl->prepare($descriptionListQuery);

                $curl->execute();

                $productRepository->setDescriptionForListByListQuery($productsById, [$descriptionListQuery]);
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

            $helper = new \Enter\Helper\Template();

            $response = ['order' => [
                'id' => $order->id,
                'number' => $order->number,
                'numberErp' => $order->numberErp,
                'token' => $order->token,
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
                        'media'                => $product->media,
                        'rating'               => $product->rating ? [
                            'score'       => $product->rating->score,
                            'starScore'   => $product->rating->starScore,
                            'reviewCount' => $product->rating->reviewCount,
                        ] : null,
                        'favorite'        => isset($product->favorite) ? $product->favorite : null,
                        'partnerOffers'   => $productRepository->getPartnerOffers($product),
                        'storeLabel'      => $product->storeLabel,
                    ];
                }, $order->product),
                'paySum' => $order->paySum,
                'discountSum' => $order->discountSum,
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