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
            $productRepository = new Repository\Product();

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
                        /** @var Model\Order $order */
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

                $productRepository->setDescriptionForIdIndexedListByQueryList($productsById, [$descriptionListQuery]);
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

            $response = ['order' => [
                'id' => $order->id,
                'number' => $order->number,
                'numberErp' => $order->numberErp,
                'token' => $order->token,
                'sum' => $order->sum,
                'shopId' => $order->shopId,
                'address' => $order->address,
                'createdAt' => $order->createdAt,
                'updatedAt' => $order->updatedAt,
                'product' => $this->getProductList($order->product),
                'paySum' => $order->paySum,
                'discountSum' => $order->discountSum,
                'subwayId' => $order->subwayId,
                'deliveries' => $order->deliveries,
                'interval' => $order->interval,
                'shop' => $order->shop,
                'point' => $order->point,
            ]];

            return new Http\JsonResponse($response);
        }
    }
}