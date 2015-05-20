<?php

namespace EnterMobileApplication\Controller\Cart {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Controller\Cart\Split\Response;

    class Split {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $session = $this->getSession();
            $cartRepository = new \EnterRepository\Cart();
            $orderRepository = new \EnterRepository\Order();
            $productRepository = new \EnterRepository\Product();

            // ответ
            $response = new Response();

            // ид магазина
            $shopId = is_scalar($request->query['shopId']) ? (string)$request->query['shopId'] : null;

            // изменения
            $changeData = $request->data['change'] ?: null;

            // данные о корзине
            if (empty($request->data['cart']['products'][0]['id'])) {
                throw new \Exception('Не передан параметр cart.products[0].id', Http\Response::STATUS_BAD_REQUEST);
            }

            // предыдущее разбиение
            $previousSplitData = null;
            if ($changeData) {
                $previousSplitData = $session->get($config->order->splitSessionKey);
            }

            $cart = new Model\Cart();
            foreach ($request->data['cart']['products'] as $productItem) {
                $cartProduct = new Model\Cart\Product($productItem);
                $cartRepository->setProductForObject($cart, $cartProduct);
            }

            /** @var Model\Cart\Product[] $cartProductsById */
            $cartProductsById = []; // товары в корзине по ид
            foreach ($cart->product as $cartProduct) {
                $cartProductsById[$cartProduct->id] = $cartProduct;
            }

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            // запрос магазина
            $shopItemQuery = null;
            if ($shopId) {
                $shopItemQuery = new Query\Shop\GetItemById($shopId);
                $curl->prepare($shopItemQuery)->execute();
            }

            $curl->execute();

            // регион
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            // магазин
            $shop = $shopItemQuery ? (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery) : null;
            if ($shopId && !$shop) {
                $this->getLogger()->push(['type' => 'warn', 'message' => 'Магазин не найден', 'shopId' => $shopId, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
            }

            // запрос на разбиение корзины
            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                $region,
                $shop,
                null,
                (array)$previousSplitData,
                $changeData ? $cartRepository->dumpSplitChange($changeData, $previousSplitData) : []
            );
            $splitQuery->setTimeout(10 * $config->coreService->timeout);
            $curl->prepare($splitQuery);

            $curl->execute();

            // разбиение
            try {
                $splitData = $splitQuery->getResult();

                // добавление данных о корзине
                $splitData['cart'] = [
                    'product_list' => array_map(
                        function(Model\Cart\Product $cartProduct) {
                            return [
                                'id'       => $cartProduct->id,
                                'quantity' => $cartProduct->quantity,
                                'sender'   => $cartProduct->sender,
                                'meta'     => $cartProduct->clientMeta,
                            ];
                        },
                        $cart->product
                    ),
                ];

                // сохранение в сессии
                $session->set($config->order->splitSessionKey, $splitData);

                $response->split = new Model\Cart\Split($splitData);
                $response->split->region = $region;

                // MAPI-4
                $productIds = [];
                foreach ($response->split->orders as $order) {
                    foreach ($order->products as $product) {
                        $product->meta = isset($cartProductsById[$product->id]) ? $cartProductsById[$product->id]->clientMeta : null; // FIXME

                        $productIds[] = $product->id;
                    }
                }

                $productListQuery = new Query\Product\GetListByIdList($productIds, $region->id);
                $curl->prepare($productListQuery);

                $curl->execute();

                // список товаров
                $productsById = $productListQuery ? $productRepository->getIndexedObjectListByQueryList([$productListQuery]) : [];

                if ($productsById) {
                    // MAPI-9
                    // запрос списка медиа для товаров
                    $descriptionListQuery = new Query\Product\GetDescriptionListByUiList(
                        array_map(function(Model\Product $product) { return $product->ui; }, $productsById),
                        [
                            'media'       => true,
                            'media_types' => ['main'], // только главная картинка
                        ]
                    );
                    $curl->prepare($descriptionListQuery);

                    $curl->execute();

                    // товары по ui
                    $productsByUi = [];
                    call_user_func(function() use (&$productsById, &$productsByUi) {
                        foreach ($productsById as $product) {
                            $productsByUi[$product->ui] = $product;
                        }
                    });

                    // медиа для товаров
                    $productRepository->setDescriptionForListByListQuery($productsByUi, $descriptionListQuery);

                    foreach ($response->split->orders as $order) {
                        foreach ($order->products as $product) {
                            $product->media = isset($productsById[$product->id]) ? $productsById[$product->id]->media : []; // FIXME
                        }
                    }
                }

                // товары в деталях ошибок
                try {
                    foreach ($response->split->errors as $error) {
                        $productId = !empty($error->detail['product']['id']) ? $error->detail['product']['id'] : null;
                        /** @var Model\Product|null $product */
                        $product = ($productId && isset($productsById[$productId])) ? $productsById[$productId] : null;

                        if (!$product) continue;

                        $error->detail['product'] += [
                            'name'    => $product->name,
                            'webName' => $product->webName,
                            'media'   => $product->media,
                        ];
                    }
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                }

                // type fix
                foreach ($response->split->orders as $order) {
                    if (!(bool)$order->groupedPossiblePointIds) {
                        $order->groupedPossiblePointIds = null;
                    }
                }

            } catch (Query\CoreQueryException $e) {
                $response->errors = $orderRepository->getErrorList($e);
            }

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Cart\Split {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $errors = [];
        /** @var array */
        public $split;
     }
}