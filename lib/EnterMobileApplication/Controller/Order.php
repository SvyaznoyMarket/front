<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterRepository as Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Order\Response;

    class Order {
        use ConfigTrait, CurlTrait, LoggerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();
            $config = $this->getConfig();

            $response = new Response();

            // токен для получения заказа
            $accessToken = is_string($request->query['accessToken']) ? $request->query['accessToken'] : null;

            // запрос заказа
            $itemQuery = new Query\Order\GetItemByAccessToken($accessToken);
            $itemQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($itemQuery);

            $curl->execute();

            // заказ
            $order = (new Repository\Order())->getObjectByQuery($itemQuery);

            $orders = [$order];

            // магазины
            $shopsById = [];
            foreach ([$order] as $order) {
                if (!$order->shopId) continue;
                $shopsById[$order->shopId] = null;
            }

            try {
                if ((bool)$shopsById) {
                    $shopRepository = new \EnterRepository\Shop();

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
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $orderProductsById = [];
            foreach ($orders as $order) {
                foreach ((array)$order->product as $orderProduct) {
                    $orderProductsById[$orderProduct->id] = $orderProduct;
                }
            }

            $productListQuery = null;
            if ((bool)$orderProductsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($orderProductsById), $order->regionId);
                $curl->prepare($productListQuery);
            }

            $curl->execute();

            // товары сгруппированные по id
            $productsById = [];
            try {
                $productsById = $productListQuery ? (new Repository\Product())->getIndexedObjectListByQueryList([$productListQuery]) : [];
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller', 'order']]);
            }

            // товары
            foreach ($orders as $order) {
                foreach ((array)$order->product as $i => $orderProduct) {
                    $product = isset($productsById[$orderProduct->id]) ? $productsById[$orderProduct->id] : null;
                    if (!$product) continue;

                    $product->price = $orderProduct->price;
                    $product->quantity = $orderProduct->quantity; // FIXME
                    $product->sum = $orderProduct->sum; // FIXME

                    $order->product[$i] = $product;
                }
            }

            // ответ
            $response->order = $order;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Order {
    use EnterModel as Model;

    class Response {
        /** @var Model\Order|null */
        public $order;
    }
}