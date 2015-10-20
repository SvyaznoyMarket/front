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
                $changeData ? $this->dumpChange($changeData, $previousSplitData) : []
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

                $this->setPointImageUrls($response->split->pointGroups);

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
                            'label'       => true,
                            'brand'       => true,
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

            // Убираем из /Cart/Split 1.3 и ниже все точки доставки кроме тех, которые поддерживаются мобильными приложениями
            call_user_func(function() use(&$response) {
                foreach ($response->split->deliveryMethods as $key => $value) {
                    if ($value->pointToken && !in_array($value->pointToken, ['shops', 'shops_svyaznoy', 'self_partner_pickpoint'], true)) {
                        unset($response->split->deliveryMethods[$key]);
                    }
                }
                $response->split->deliveryMethods = array_values($response->split->deliveryMethods);
                
                foreach ($response->split->deliveryGroups as $key => $value) {
                    $hasGroup = false;
                    foreach ($response->split->deliveryMethods as $value2) {
                        if ($value2->groupId == $value->id) {
                            $hasGroup = true;
                            break;
                        }
                    }
                    
                    if (!$hasGroup) {
                        unset($response->split->deliveryGroups[$key]);
                    }
                }
                $response->split->deliveryGroups = array_values($response->split->deliveryGroups);

                foreach ($response->split->pointGroups as $key => $value) {
                    if ($value->token && !in_array($value->token, ['shops', 'shops_svyaznoy', 'self_partner_pickpoint'], true)) {
                        unset($response->split->pointGroups[$key]);
                    }
                }
                $response->split->pointGroups = array_values($response->split->pointGroups);

                foreach ($response->split->orders as $key => $value) {
                    foreach ($value->possibleDeliveryMethodTokens as $key2 => $value2) {
                        if ($value2 && strpos($value2, 'self_partner_') === 0 && !preg_match('/^self_partner_pickpoint($|_)/', $value2)) {
                            unset($response->split->orders[$key]->possibleDeliveryMethodTokens[$key2]);
                        }
                    }
                    $response->split->orders[$key]->possibleDeliveryMethodTokens = array_values($response->split->orders[$key]->possibleDeliveryMethodTokens);


                    foreach ($value->possiblePoints as $key2 => $value2) {
                        if ($value2->groupToken && !in_array($value2->groupToken, ['shops', 'shops_svyaznoy', 'self_partner_pickpoint'], true)) {
                            unset($response->split->orders[$key]->possiblePoints[$key2]);
                        }
                    }
                    $response->split->orders[$key]->possiblePoints = array_values($response->split->orders[$key]->possiblePoints);
                }
            });

            // response
            return new Http\JsonResponse($response);
        }

        /**
         * @param Model\Cart\Split\PointGroup[] $pointGroups
         */
        private function setPointImageUrls($pointGroups) {
            foreach ($pointGroups as $pointGroup) {
                switch ($pointGroup->token) {
                    case 'shops':
                        $image = 'enter.png';
                        break;
                    case 'self_partner_pickpoint':
                        $image = 'pickpoint.png';
                        break;
                    case 'self_partner_svyaznoy':
                    case 'shops_svyaznoy':
                        $image = 'svyaznoy.png';
                        break;
                    case 'self_partner_euroset':
                        $image = 'euroset.png';
                        break;
                    case 'self_partner_hermes':
                        $image = 'hermesdpd.png';
                        break;
                    default:
                        $image = '';
                        break;
                }

                if ($image) {
                    $pointGroup->imageUrl = 'http://' . $this->getConfig()->hostname . '/' . $this->getConfig()->version . '/img/points/' . $image;
                }
            }
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
                        'products'  => [],
                        'discounts' => [],
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

                    // комментарий
                    if (array_key_exists('comment', $orderItem)) {
                        $dump['orders'][$blockName]['comment'] = $orderItem['comment'];
                    }

                    // способ оплаты
                    if (array_key_exists('paymentMethodId', $orderItem)) {
                        $dump['orders'][$blockName]['payment_method_id'] = $orderItem['paymentMethodId'];
                    }

                    // количество товаров
                    if (isset($orderItem['products'][0])) {
                        $quantitiesByProductId = [];
                        foreach ($orderItem['products'] as $productItem) {
                            if (empty($productItem['id']) || !isset($productItem['quantity'])) {
                                $this->getLogger()->push(['type' => 'warn', 'message' => 'Не указан ид или не найден товар', 'product' => $productItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                                continue;
                            }

                            $quantitiesByProductId[$productItem['id']] = (int)$productItem['quantity'];
                        }

                        $productItem = null;
                        foreach ($dump['orders'][$blockName]['products'] as &$productItem) {
                            if (!isset($productItem['id']) || !isset($quantitiesByProductId[$productItem['id']])) {
                                $this->getLogger()->push(['type' => 'warn', 'message' => 'Не указан ид или не найден товар', 'product' => $productItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                                continue;
                            }

                            $productItem['quantity'] = $quantitiesByProductId[$productItem['id']];
                        }
                        unset($productItem);
                    }

                    // скидки
                    if (isset($orderItem['discounts'][0])) {
                        $discountItem = null;
                        foreach ($orderItem['discounts'] as $discountItem) {
                            $this->getLogger()->push(['message' => 'Применение купона', 'discount' => $discountItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);

                            if (empty($discountItem['number'])) {
                                $this->getLogger()->push(['type' => 'warn', 'message' => 'Не передан номер купона', 'discount' => $discountItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                                continue;
                            }

                            if (isset($discountItem['delete']) && $discountItem['delete']) { // удаление купона
                                $isDeleted = false;
                                // поиск существующей скидки
                                foreach ($dump['orders'][$blockName]['discounts'] as $i => $existsDiscountItem) {
                                    if ($existsDiscountItem['number'] == $discountItem['number']) {
                                        // удаление найденной скидки
                                        unset($dump['orders'][$blockName]['discounts'][$i]);
                                    }
                                }
                                if (!$isDeleted) {
                                    $this->getLogger()->push(['type' => 'warn', 'message' => 'Купон не найден', 'discount' => $discountItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                                }
                            } else { // добавление купона
                                $dump['orders'][$blockName]['discounts'][] = ['number' => $discountItem['number'], 'name' => null, 'type' => null, 'discount' => null];
                            }
                        }
                        unset($discountItem);
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
                    $dump['user_info']['email'] = !empty($changeData['user']['email']) ? $changeData['user']['email'] : null;
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
        /** @var Model\Cart\Split */
        public $split;
     }
}