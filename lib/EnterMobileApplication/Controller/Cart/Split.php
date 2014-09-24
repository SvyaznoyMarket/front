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

            // ответ
            $response = new Response();

            // данные о корзине
            if (empty($request->data['cart']['products'][0]['id'])) {
                throw new \Exception('Не передан параметр cart.products[0].id');
            }

            // изменения
            $changeData = $request->data['change'] ?: null;

            // предыдущее разбиение
            $previousSplitData = null;
            if ($changeData) {
                $previousSplitData = $session->get($config->order->splitSessionKey);
            }

            $cart = new Model\Cart();
            foreach ($request->data['cart']['products'] as $productItem) {
                $cartRepository->setProductForObject($cart, new Model\Cart\Product($productItem));
            }

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId');
            }

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                new Model\Region(['id' => $region->id]),
                null,
                null,
                (array)$previousSplitData,
                $changeData ? $this->dumpChange($changeData, $previousSplitData) : []
            );
            $splitQuery->setTimeout($config->coreService->timeout * 2);
            $curl->prepare($splitQuery);

            $curl->execute();

            // разбиение
            try {
                $splitData = $splitQuery->getResult();

                // добавление данных о корзине
                $splitData['cart'] = [
                    'product_list' => array_map(function(Model\Cart\Product $product) { return ['id' => $product->id, 'quantity' => $product->quantity]; }, $cart->product),
                ];

                // сохранение в сессии
                $session->set($config->order->splitSessionKey, $splitData);

                $response->split = new Model\Cart\Split($splitData);
            } catch (Query\CoreQueryException $e) {
                $response->errors = $orderRepository->getErrorList($e);
            }

            // response
            return new Http\JsonResponse($response);
        }

        private function dumpChange($changeData, $previousSplitData) {
            $dump = [];

            if (!empty($changeData['orders']) && is_array($changeData['orders'])) {
                foreach ($changeData['orders'] as $orderItem) {
                    $blockName = isset($orderItem['blockName']) ? $orderItem['blockName'] : null;

                    if (!$blockName || !isset($previousSplitData['orders'][$blockName])) {
                        $this->getLogger()->push(['type' => 'warn', 'message' => 'Передан несуществующий блок заказа', 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                        continue;
                    }

                    $dump['orders'][$blockName] = $previousSplitData['orders'][$blockName];

                    // метод получения
                    if (isset($orderItem['delivery']['methodToken'])) {
                        $dump['orders'][$blockName]['delivery'] = [
                            'delivery_method_token' => $orderItem['delivery']['methodToken'],
                        ];
                    }

                    // точка получения
                    if (isset($orderItem['delivery']['point']['id']) && isset($orderItem['delivery']['point']['groupToken'])) {
                        $dump['orders'][$blockName]['delivery']['point'] = [
                            'id'    => $orderItem['delivery']['point']['id'],
                            'token' => $orderItem['delivery']['point']['groupToken'],

                        ];
                    }

                    // дата получения
                    if (isset($orderItem['delivery']['date'])) {
                        $dump['orders'][$blockName]['delivery']['date'] = $orderItem['delivery']['date'];
                    }

                    // интервал
                    if (isset($orderItem['delivery']['interval'])) {
                        $dump['orders'][$blockName]['delivery']['interval'] = $orderItem['delivery']['interval'];
                    }
                }
            }
            //die(var_dump($dump));

            return $dump;
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