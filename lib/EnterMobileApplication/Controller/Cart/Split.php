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

            // изменения
            $changeData = $request->data['change'] ?: null;

            // данные о корзине
            if (empty($request->data['cart']['products'][0]['id'])) {
                throw new \Exception('Не передан параметр cart.products[0].id');
            }

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
            $response->region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                new Model\Region(['id' => $response->region->id]),
                null,
                null,
                (array)$previousSplitData,
                $changeData ? $this->dumpChange($changeData, $previousSplitData) : []
            );
            $splitQuery->setTimeout($config->coreService->timeout * 4);
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

                // обогащение данными о товарах
                /** @var Model\Product[] $productsById */
                $productsById = [];
                foreach ($response->split->errors as $error) {
                    $productId = !empty($error->detail['product']['id']) ? $error->detail['product']['id'] : null;
                    if (!$productId) continue;

                    $productsById[$productId] = null;
                }
                if ((bool)$productsById) {
                    $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $response->region->id);
                    $curl->prepare($productListQuery)->execute();

                    try {
                        foreach ($productListQuery->getResult() as $productItem) {
                            $productId = @$productItem['id'] ? (string)$productItem['id'] : null;
                            if (!$productId) continue;

                            $productsById[$productId] = new Model\Product($productItem);
                        }

                        foreach ($response->split->errors as $error) {
                            $productId = !empty($error->detail['product']['id']) ? $error->detail['product']['id'] : null;
                            /** @var Model\Product|null $product */
                            $product = ($productId && isset($productsById[$productId])) ? $productsById[$productId] : null;

                            if (!$product) continue;

                            $error->detail['product'] += [
                                'name'    => $product->name,
                                'webName' => $product->webName,
                            ];
                        }
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                    }
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

        /**
         * @param $changeData
         * @param $previousSplitData
         * @return array
         */
        private function dumpChange($changeData, $previousSplitData) {
            $dump = [];

            // заказ
            if (!empty($changeData['orders']) && is_array($changeData['orders'])) {
                foreach ($changeData['orders'] as $orderItem) {
                    $blockName = isset($orderItem['blockName']) ? $orderItem['blockName'] : null;

                    if (!$blockName || !isset($previousSplitData['orders'][$blockName])) {
                        $this->getLogger()->push(['type' => 'warn', 'message' => 'Передан несуществующий блок заказа', 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                        continue;
                    }

                    $dump['orders'][$blockName] = $previousSplitData['orders'][$blockName] + [
                        'products' => [],
                    ];

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

                    // количество товаров
                    if (isset($orderItem['products'][0])) {
                        $quantitiesByProductId = [];
                        foreach ($orderItem['products'] as $productItem) {
                            if (empty($productItem['id']) || !isset($productItem['quantity'])) continue;

                            $quantitiesByProductId[$productItem['id']] = (int)$productItem['quantity'];
                        }

                        $productItem = null;
                        foreach ($dump['orders'][$blockName]['products'] as &$productItem) {
                            if (!isset($productItem['id']) || !isset($quantitiesByProductId[$productItem['id']])) continue;

                            $productItem['quantity'] = $quantitiesByProductId[$productItem['id']];
                        }
                        unset($productItem);
                    }
                }
            }

            // инфо пользователя
            if (!empty($changeData['user'])) {
                $dump['user_info'] = $previousSplitData['user_info'];

                if (array_key_exists('phone', $changeData['user'])) {
                    $dump['user_info']['phone'] = $changeData['user']['phone'];
                }
                if (array_key_exists('lastName', $changeData['user'])) {
                    $dump['user_info']['last_name'] = $changeData['user']['lastName'];
                }
                if (array_key_exists('firstName', $changeData['user'])) {
                    $dump['user_info']['first_name'] = $changeData['user']['firstName'];
                }
                if (array_key_exists('email', $changeData['user'])) {
                    $dump['user_info']['email'] = $changeData['user']['email'];
                }
                if (array_key_exists('bonusCardNumber', $changeData['user'])) {
                    $dump['user_info']['bonus_card_number'] = $changeData['user']['bonusCardNumber'];
                }
                if (array_key_exists('address', $changeData['user'])) {
                    if (array_key_exists('street', $changeData['user']['address'])) {
                        $dump['user_info']['address']['street'] = $changeData['user']['address']['street'];
                    }
                    if (array_key_exists('building', $changeData['user']['address'])) {
                        $dump['user_info']['address']['building'] = $changeData['user']['address']['building'];
                    }
                    if (array_key_exists('number', $changeData['user']['address'])) {
                        $dump['user_info']['address']['number'] = $changeData['user']['address']['number'];
                    }
                    if (array_key_exists('apartment', $changeData['user']['address'])) {
                        $dump['user_info']['address']['apartment'] = $changeData['user']['address']['apartment'];
                    }
                    if (array_key_exists('floor', $changeData['user']['address'])) {
                        $dump['user_info']['address']['floor'] = $changeData['user']['address']['floor'];
                    }
                    if (array_key_exists('subwayName', $changeData['user']['address'])) {
                        $dump['user_info']['address']['metro_station'] = $changeData['user']['address']['subwayName'];
                    }
                    if (array_key_exists('kladrId', $changeData['user']['address'])) {
                        $dump['user_info']['address']['kladr_id'] = $changeData['user']['address']['kladrId'];
                    }
                }
            }

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
        /** @var Model\Region|null */
        public $region;
    }
}