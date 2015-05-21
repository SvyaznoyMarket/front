<?php

namespace EnterAggregator\Controller\Cart {

    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;

    class Split {
        use ConfigTrait, LoggerTrait, CurlTrait;

        public function execute(Split\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $cartRepository = new \EnterRepository\Cart();
            $orderRepository = new \EnterRepository\Order();
            $productRepository = new \EnterRepository\Product();

            // ответ
            $response = new Split\Response();

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);

            // запрос магазина
            $shopItemQuery = null;
            if ($request->shopId) {
                $shopItemQuery = new Query\Shop\GetItemById($request->shopId);
                $curl->prepare($shopItemQuery)->execute();
            }

            $curl->execute();

            // регион
            $response->region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            // магазин
            $shop = $shopItemQuery ? (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery) : null;
            if ($request->shopId && !$shop) {
                $this->getLogger()->push(['type' => 'warn', 'message' => 'Магазин не найден', 'shopId' => $request->shopId, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
            }

            // запрос на разбиение корзины
            $splitQuery = new Query\Cart\Split\GetItem(
                $request->cart,
                $response->region,
                $shop,
                null,
                (array)$request->previousSplitData,
                $request->changeData ? $cartRepository->dumpSplitChange($request->changeData, $request->previousSplitData) : []
            );
            $splitQuery->setTimeout(10 * $config->coreService->timeout);
            $curl->prepare($splitQuery);

            $curl->execute();

            // индексация товаров из корзины по идентификатору
            /** @var Model\Cart\Product[] $cartProductsById */
            $cartProductsById = []; // товары в корзине по ид
            foreach ($request->cart->product as $cartProduct) {
                $cartProductsById[$cartProduct->id] = $cartProduct;
            }

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
                        $request->cart->product
                    ),
                ];

                // сохранение в сессии
                if ($request->splitReceivedSuccessfullyCallback && is_callable($request->splitReceivedSuccessfullyCallback->handler)) {
                    try {
                        $request->splitReceivedSuccessfullyCallback->splitData = $splitData;
                        call_user_func($request->splitReceivedSuccessfullyCallback->handler);
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                    }
                }

                $response->split = new Model\Cart\Split($splitData);
                $response->split->region = $response->region;

                // MAPI-4
                $productIds = [];
                foreach ($response->split->orders as $order) {
                    foreach ($order->products as $product) {
                        $product->meta = isset($cartProductsById[$product->id]) ? $cartProductsById[$product->id]->clientMeta : null; // FIXME

                        $productIds[] = $product->id;
                    }
                }

                $productListQuery = new Query\Product\GetListByIdList($productIds, $response->region->id);
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

            return $response;
        }

        /**
         * @return Split\Request
         */
        public function createRequest() {
            return new Split\Request();
        }
    }
}

namespace EnterAggregator\Controller\Cart\Split {
    use EnterModel as Model;

    class Request {
        /** @var string|null */
        public $regionId;
        /** @var string|null */
        public $shopId;
        /** @var Model\Cart */
        public $cart;
        /**
         * Сырые данные от ядра о предыдущем разбиении
         *
         * @var array
         */
        public $previousSplitData;
        /**
         * Изменения для разбиения
         *
         * @var array
         */
        public $changeData;
        /**
         * Обработчик, который вызывается немедленно при получении разбиения от ядра
         *
         * @var Request\SplitReceivedSuccessfullyCallback
         */
        public $splitReceivedSuccessfullyCallback;

        public function __construct() {
            $this->splitReceivedSuccessfullyCallback = new Request\SplitReceivedSuccessfullyCallback();
        }
    }

    class Response {
        /** @var array */
        public $errors = [];
        /** @var Model\Cart\Split|null */
        public $split;
        /** @var Model\Region|null */
        public $region;
     }
}

namespace EnterAggregator\Controller\Cart\Split\Request {
    use EnterModel as Model;

    class SplitReceivedSuccessfullyCallback {
        /** @var callable|null */
        public $handler;
        /**
         * Сырые данные от ядра
         *
         * @var array
         */
        public $splitData;
    }
}