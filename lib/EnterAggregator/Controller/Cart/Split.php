<?php

namespace EnterAggregator\Controller\Cart {

    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class Split {
        use ConfigTrait, LoggerTrait, CurlTrait;

        public function execute(Split\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $orderRepository = new Repository\Order();
            $productRepository = new Repository\Product();

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
            
            // запрос пользователя
            $userItemQuery = null;
            if ($request->userToken && (0 !== strpos($request->userToken, 'anonymous-'))) {
                $userItemQuery = new Query\User\GetItemByToken($request->userToken);
                $curl->prepare($userItemQuery);
            }
            
            if ($request->enrichCart) {
                $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($request->cart, $response->region->id);
                $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($request->cart, $response->region->id);
            }

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
                $request->changeData ? $request->changeData : [],
                $request->userFromSplit
            );
            $splitQuery->setTimeout(10 * $config->coreService->timeout);
            $curl->prepare($splitQuery);

            $curl->execute($splitQuery->getTimeout() / 2, 2);
            
            // пользователь
            try {
                if ($userItemQuery) {
                    $response->user = (new Repository\User())->getObjectByQuery($userItemQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            if ($request->enrichCart) {
                (new \EnterRepository\Cart())->updateObjectByQuery($request->cart, $cartItemQuery, $cartProductListQuery);
            }

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

                $response->split = new Model\Cart\Split($splitData, (bool)$request->formatSplit);
                $response->split->region = $response->region;

                // FRONT-88
                foreach ($response->split->orders as $order) {
                    if ($order->sum > $config->order->prepayment->priceLimit) {
                        foreach ($order->possiblePaymentMethodIds as $i => $possiblePaymentMethodId) {
                            if (in_array($possiblePaymentMethodId, ['1', '2']) && (count($order->possiblePaymentMethodIds) > 1)) {
                                unset($order->possiblePaymentMethodIds[$i]);
                            }
                        }
                    }
                }

                // MAPI-4
                $productIds = [];
                foreach ($response->split->orders as $order) {
                    foreach ($order->products as $product) {
                        $product->meta = isset($cartProductsById[$product->id]) ? $cartProductsById[$product->id]->clientMeta : null; // FIXME

                        $productIds[] = $product->id;
                    }
                }

                $productListQuery = new Query\Product\GetListByIdList($productIds, $response->region->id, ['related' => false]);
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
                            'category'    => true,
                            'label'       => true,
                            'brand'       => true,
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
                    $productRepository->setDescriptionForListByListQuery($productsByUi, [$descriptionListQuery]);

                    foreach ($response->split->orders as $order) {
                        foreach ($order->products as $product) {
                            if (isset($productsById[$product->id])) {
                                $product->url = $productsById[$product->id]->link;
                                $product->name = $productsById[$product->id]->name;
                                $product->webName = $productsById[$product->id]->webName;
                                $product->namePrefix = $productsById[$product->id]->namePrefix;
                                $product->media = $productsById[$product->id]->media;
                            }
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
            } catch (Query\CoreQueryException $e) {
                $response->errors = $orderRepository->getErrorList($e);
            } catch (\Exception $e) {
                $response->errors = [
                    ['code' => $e->getCode(), 'message' => 'Не удалось выполнить действие']
                ];
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
        public $userToken;
        /** @var string|null */
        public $regionId;
        /** @var string|null */
        public $shopId;
        /** @var Model\Cart */
        public $cart;
        /** @var bool */
        public $enrichCart = false;
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
         * Индексация разбиения как на ядре
         * @var bool
         */
        public $formatSplit = true;
        /** @var Model\Cart\Split\User */
        public $userFromSplit;
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
        /** @var Model\User|null */
        public $user;
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