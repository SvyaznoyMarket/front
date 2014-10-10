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
         * @param $isReceiveSms
         * @throws \Exception
         * @return Response
         */
        public function execute(
            $regionId,
            Model\Cart\Split $split,
            array $metas = [],
            $isReceiveSms = false
        ) {
            $config = $this->getConfig();
            $logger = $this->getLogger();
            $curl = $this->getCurl();
            $orderRepository = new Repository\Order();
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

            // FIXME: заглушка
            //$orderData = json_decode('[{"confirmed":"true","id":"7720368","is_partner":0,"number":"TD856420","number_erp":"COTD-856420","user_id":"1138","price":4690,"pay_sum":4690,"payment_invoice_id":null,"payment_url":null}]', true);

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
                } else if ($number) {
                    $orderItemQuery = new Query\Order\GetItemByNumber($number, $split->user->phone);
                } else {
                    $logger->push(['type' => 'error', 'error' => 'Не получен номер или токен заказа', 'order' => $orderItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
                    continue;
                }

                $curl->prepare($orderItemQuery);
                $orderItemQueries[] = $orderItemQuery;

                $paymentMethodListQuery = new Query\PaymentMethod\GetListByOrderNumberErp($numberErp, $regionId);
                $curl->prepare($paymentMethodListQuery);
                $paymentMethodListQueriesByOrderNumberErp[$numberErp] = $paymentMethodListQuery;
            }

            $curl->execute();

            /** @var Model\Order[] $orders */
            $orders = [];
            foreach ($orderItemQueries as $i => $orderItemQuery) {
                try {
                    $order = $orderRepository->getObjectByQuery($orderItemQuery);

                    $orders[] = $order;
                } catch (\Exception $e) {
                    $logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);

                    $orders[] = new Model\Order($orderData[$i]);
                }
            }

            $orderProductsById = [];
            foreach ($orders as $order) {
                foreach ($order->product as $orderProduct) {
                    $orderProductsById[$orderProduct->id] = $orderProduct;
                }
            }

            $productListQuery = null;
            if ((bool)$orderProductsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($orderProductsById), $regionId);
                $curl->prepare($productListQuery);
            }

            $curl->execute();

            // товары сгруппированные по id
            $productsById = [];
            try {
                $productsById = $productListQuery ? (new Repository\Product())->getIndexedObjectListByQueryList([$productListQuery]) : [];
            } catch (\Exception $e) {
                $logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            }

            // товары
            foreach ($orders as $order) {
                foreach ($order->product as $i => $orderProduct) {
                    $product = isset($productsById[$orderProduct->id]) ? $productsById[$orderProduct->id] : null;
                    if (!$product) continue;

                    $product->price = $orderProduct->price;
                    $product->quantity = $orderProduct->quantity; // FIXME
                    $product->sum = $orderProduct->sum; // FIXME

                    $order->product[$i] = $product;
                }
            }

            // возможные методы оплат
            $paymentMethodsByOrderNumber = [];
            foreach ($paymentMethodListQueriesByOrderNumberErp as $numberErp => $paymentMethodListQuery) {
                $paymentMethodsByOrderNumber[$numberErp] = $paymentMethodRepository->getIndexedObjectListByQuery($paymentMethodListQuery);
            }

            // доставка
            $orderRepository->setDeliveryTypeForObjectList($orders);

            // установка возможных методов оплат
            foreach ($orders as $order) {
                $order->paymentMethods = isset($paymentMethodsByOrderNumber[$order->number]) ? array_values((array)$paymentMethodsByOrderNumber[$order->number]) : [];
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