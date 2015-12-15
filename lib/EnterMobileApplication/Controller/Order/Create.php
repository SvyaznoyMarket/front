<?php

namespace EnterMobileApplication\Controller\Order {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Controller\Order\Create\Response;

    class Create {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $cartRepository = new \EnterRepository\Cart();
            $session = $this->getSession();

            // ответ
            $response = new Response();

            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            // данные пользователя
            $userData = (array)(isset($request->data['user']) ? $request->data['user'] : []);

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос региона
            $regionItemQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionItemQuery);

            // запрос пользователя
            $userItemQuery = null;
            if ($userAuthToken && (0 !== strpos($userAuthToken, 'anonymous-'))) {
                $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
                $curl->prepare($userItemQuery);
            }

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionItemQuery);
            if (!$region) {
                throw new \Exception(sprintf('Регион #%s не найден', $regionId));
            }

            // пользователь
            $user = null;
            try {
                if ($userItemQuery) {
                    $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $splitData = (array)$session->get($config->order->splitSessionKey);
            if (!$splitData) {
                throw new \Exception('Не найдено предыдущее разбиение');
            }

            if (!isset($splitData['cart']['product_list'])) {
                throw new \Exception('Не найдены товары в корзине');
            }

            // корзина из данных о разбиении
            $cart = new Model\Cart();
            foreach ($splitData['cart']['product_list'] as $productItem) {
                $cartProduct = new Model\Cart\Product($productItem);
                $cartRepository->setProductForObject($cart, $cartProduct);
            }

            // слияние данных о пользователе
            if (!isset($splitData['user_info'])) {
                $splitData['user_info'] = [];
            }
            if (!empty($userData['email'])) {
                $splitData['user_info']['email'] = $userData['email'];
            }
            if (!empty($userData['phone'])) {
                $splitData['user_info']['phone'] = $userData['phone'];
            }
            if (!empty($userData['lastName'])) {
                $splitData['user_info']['last_name'] = $userData['lastName'];
            }
            if (!empty($userData['firstName'])) {
                $splitData['user_info']['first_name'] = $userData['firstName'];
            }

            $split = null;
            try {
                $split = new Model\Cart\Split($splitData);

                // дополнительные свойства разбиения
                $split->region = $region;
                $split->clientIp = $request->getClientIp();

                // пользователь
                if ($user) {
                    $split->user->id = $user->id;
                    $split->user->ui = $user->ui;
                }

                // meta
                $metas = [];

                // бонусные карты
                foreach ($session->get($config->order->bonusCardSessionKey, []) as $cardItem) {
                    if (!isset($cardItem['type'])) continue;

                    if ('mnogoru' === $cardItem['type']) {
                        $meta = new Model\Order\Meta();
                        $meta->key = 'mnogo_ru_card';
                        $meta->value = $cardItem['number'];
                        $metas[] = $meta;
                    }
                }

                $controllerResponse = (new \EnterAggregator\Controller\Order\Create())->execute(
                    $region->id,
                    $split,
                    $metas
                );

                if (!$controllerResponse->errors) {
                    $session->remove($config->order->bonusCardSessionKey);
                    if ($user) {
                        $curl->prepare(new Query\Cart\ClearItem($user->ui));
                        $curl->execute();
                    }
                }

                // MAPI-4
                try {
                    call_user_func(function() use (&$controllerResponse, &$cart) {
                        /** @var Model\Cart\Product[] $cartProductsById */
                        $cartProductsById = [];
                        foreach ($cart->product as $cartProduct) {
                            $cartProductsById[$cartProduct->id] = $cartProduct;
                        }

                        foreach ($controllerResponse->orders as $order) {
                            foreach ($order->paymentMethods as $key => $paymentMethod) {
                                if ($paymentMethod->isOnline) {
                                    unset($order->paymentMethods[$key]);
                                }
                            }

                            $order->paymentMethods = array_values($order->paymentMethods);

                            foreach ($order->product as $product) {
                                $product->meta = !empty($cartProductsById[$product->id]) ? $cartProductsById[$product->id]->clientMeta : null;
                            }
                        }
                    });
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);

                throw new \Exception($e->getMessage());
            }

            $response->orders = $controllerResponse->orders;
            $response->cart = $cart;
            $response->errors = $controllerResponse->errors;

            // debug
            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            // response
            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterMobileApplication\Controller\Order\Create {
    use EnterModel as Model;

    class Response {
        /** @var Model\Order[] */
        public $errors = [];
        /** @var array */
        public $orders = [];
        /** @var Model\Cart */
        public $cart;
    }
}