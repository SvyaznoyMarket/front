<?php

namespace EnterAggregator\Controller\Order {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;
    use EnterTerminal\Controller;
    use EnterAggregator\Controller\Order\Create\Response;

    class Create {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param $regionId
         * @param Model\Cart\Split $split
         * @param Model\Order\Meta[] $metas
         * @throws \Exception
         * @return Response
         */
        public function execute(
            $regionId,
            Model\Cart\Split $split,
            array $metas = []
        ) {
            $config = $this->getConfig();
            $logger = $this->getLogger();
            $curl = $this->getCurl();
            $orderRepository = new Repository\Order();
            $productRepository = new Repository\Product();
            $paymentMethodRepository = new Repository\PaymentMethod();

            // ответ
            $response = new Response();

            // создание заказа
            $createOrderQuery = new Query\Order\CreatePacketBySplit($split, $metas);
            $createOrderQuery->setTimeout(90);

            $curl->query($createOrderQuery);

            $orderData = [];
            try {
                $orderData = $createOrderQuery->getResult();
                if (!count($orderData)) { // костыль для ядра
                    $response->errors[] = ['code' => 500, 'message' => 'Заказы не подтверждены'];
                }
            } catch (Query\CoreQueryException $e) {
                $response->errors = $orderRepository->getErrorList($e);
                $logger->push(['type' => 'error', 'error' => $e, 'query' => $createOrderQuery, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            } catch (\Exception $e) {
                $response->errors[] = ['code' => $e->getCode(), 'message' => 'Невозможно создать заказ'];
                $logger->push(['type' => 'error', 'error' => $e, 'query' => $createOrderQuery, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order', 'critical']]);
            }

            /** @var \Enter\Curl\Query[] $orderItemQueries */
            $orderItemQueries = [];
            /** @var \Enter\Curl\Query[] $paymentMethodListQueriesByOrderNumberErp */
            $paymentMethodListQueriesByOrderNumberErp = [];
            foreach ($orderData as $orderItem) {
                $number = !empty($orderItem['number']) ? (string)$orderItem['number'] : null;
                $numberErp = !empty($orderItem['number_erp']) ? (string)$orderItem['number_erp'] : null;
                $accessToken = !empty($orderItem['access_token']) ? (string)$orderItem['access_token'] : null;

                if ($accessToken) {
                    $orderItemQuery = new Query\Order\GetItemByAccessToken($accessToken);
                    $orderItemQuery->setTimeout(5 * $config->coreService->timeout);
                } else if ($number) {
                    $orderItemQuery = new Query\Order\GetItemByNumber($number, $split->user->phone);
                    $orderItemQuery->setTimeout(5 * $config->coreService->timeout);
                } else {
                    $logger->push(['type' => 'error', 'error' => 'Не получен номер или токен заказа', 'order' => $orderItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
                    continue;
                }

                //$curl->prepare($orderItemQuery); // Не дергаем order/get, т.к. проблемы с онным на ядре
                //$orderItemQueries[] = $orderItemQuery;

                $paymentMethodListQuery = new Query\PaymentMethod\GetListByOrderNumberErp($numberErp, $regionId);
                $paymentMethodListQuery->setTimeout(4 * $config->coreService->timeout);
                $curl->prepare($paymentMethodListQuery);
                $paymentMethodListQueriesByOrderNumberErp[$numberErp] = $paymentMethodListQuery;
            }

            $curl->execute();

            /** @var Model\Order[] $orders */
            $orders = [];
            if ((bool)$orderItemQueries) {
                foreach ($orderItemQueries as $i => $orderItemQuery) {
                    try {
                        $order = $orderRepository->getObjectByQuery($orderItemQuery);

                        $orders[] = $order;
                    } catch (\Exception $e) {
                        $logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);

                        $orders[] = new Model\Order($orderData[$i]);
                    }
                }
            } else {
                foreach ($orderData as $orderItem) {
                    if (!isset($orderItem['number'])) continue;

                    $orders[] = new Model\Order($orderItem);
                }
            }

            $orderProductsById = [];
            foreach ($orders as $order) {
                foreach ((array)$order->product as $orderProduct) {
                    $orderProductsById[$orderProduct->id] = $orderProduct;
                }
            }

            // запрос товаров
            $productListQuery = null;
            $descriptionListQuery = null;
            if ($orderProductsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($orderProductsById), $regionId);
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

            // товары индексированные по id
            $productsById = [];
            try {
                $productsById = $productListQuery ? (new Repository\Product())->getIndexedObjectListByQueryList([$productListQuery], [$descriptionListQuery]) : [];
            } catch (\Exception $e) {
                $logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            }

            // запрос на проверку товаров в избранном
            $favoriteListQuery = null;
            if ($orderProductsById && $split->user->ui) {
                $favoriteListQuery = new Query\User\Favorite\CheckListByUserUi($split->user->ui, array_map(function(Model\Product $product) { return $product->ui; }, $productsById));
                $favoriteListQuery->setTimeout($config->crmService->timeout / 2);
                $curl->prepare($favoriteListQuery);

                $curl->execute();
            }

            // товары в избранном
            try {
                if ($favoriteListQuery) {
                    $productRepository->setFavoriteForObjectListByQuery($productsById, $favoriteListQuery);
                }
            } catch (\Exception $e) {
                $logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            }

            // товары
            foreach ($orders as $order) {
                foreach ($order->product as $orderProduct) {
                    $product = isset($productsById[$orderProduct->id]) ? $productsById[$orderProduct->id] : null;
                    if (!$product) continue;

                    $orderProduct->fromArray(array_merge(json_decode(json_encode($product), true), [
                        'price' => $orderProduct->price,
                        'quantity' => $orderProduct->quantity,
                        'sum' => $orderProduct->sum,
                    ]));
                }
            }

            // возможные методы оплат
            $paymentMethodsByOrderNumberErp = [];
            foreach ($paymentMethodListQueriesByOrderNumberErp as $numberErp => $paymentMethodListQuery) {
                $paymentMethodsByOrderNumberErp[$numberErp] = $paymentMethodRepository->getIndexedObjectListByQuery($paymentMethodListQuery);
            }

            // доставка
            $orderRepository->setDeliveryTypeForObjectList($orders);

            // установка возможных методов оплат
            foreach ($orders as $order) {
                $order->paymentMethods = isset($paymentMethodsByOrderNumberErp[$order->numberErp]) ? array_values((array)$paymentMethodsByOrderNumberErp[$order->numberErp]) : [];
                foreach ($order->paymentMethods as $paymentMethod) {
                    // MAPI-179
                    if (!$paymentMethod->sum) {
                        $paymentMethod->sum = $order->paySum;
                    }
                }
            }

            // магазин
            $shopsById = [];
            foreach ($orders as $order) {
                if (!$order->shopId) continue;
                $shopsById[$order->shopId] = null;
            }

            try {
                if ((bool)$shopsById) {
                    $shopRepository = new Repository\Shop();

                    $shopListQuery = new Query\Shop\GetListByIdList(array_keys($shopsById));
                    $curl->prepare($shopListQuery)->execute();

                    $shopsById = $shopRepository->getIndexedObjectListByQuery($shopListQuery);
                    foreach ($orders as $order) {
                        $shop = ($order->shopId && isset($shopsById[$order->shopId])) ? $shopsById[$order->shopId] : null;
                        if (!$shop) continue;

                        $order->shop = $shop;
                    }
                }
            } catch (\Exception $e) {
                $logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            }

            $response->orders = $orders;
            $response->productsById = $productsById;

            return $response;
        }
    }
}

namespace EnterAggregator\Controller\Order\Create {
    use EnterModel as Model;

    class Response {
        /** @var array[] */
        public $errors = [];
        /** @var Model\Order[] */
        public $orders = [];
        /** @var Model\Product[] */
        public $productsById;
    }
}