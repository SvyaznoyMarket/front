<?php

namespace EnterTerminal\Controller\Order {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Repository;
    use EnterTerminal\Controller;
    use EnterTerminal\Controller\Order\Create\Response;

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
            $session = $this->getSession();
            $cartRepository = new \EnterRepository\Cart();

            // ответ
            $response = new Response();

            // данные пользователя
            $userData = (array)$request->data['user_info'];

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            // запрос региона
            $regionItemQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionItemQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionItemQuery);
            if (!$region) {
                throw new \Exception(sprintf('Регион #%s не найден', $regionId));
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
                $cartRepository->setProductForObject($cart, new Model\Cart\Product($productItem));
            }

            // слияние данных о пользователе
            $splitData['user_info'] = array_merge($splitData['user_info'], $userData);

            $split = null;
            try {
                $split = new Model\Cart\Split($splitData);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);

                throw new \Exception('Неверные данные для разбиения корзины. ' . $e->getMessage());
            }

            // дополнительные свойства разбиения
            $split->region = $region;
            $split->clientIp = $request->getClientIp();

            // meta
            $metas = [];

            try {
                // запрос товаров
                $productListQuery = new Query\Product\GetListByIdList(array_map(function(Model\Cart\Product $cartProduct) { return $cartProduct->id; }, $cart->product), $config->region->defaultId);
                $curl->prepare($productListQuery);

                $curl->execute();

                $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList($productListQuery);

                // установка sender-а
                foreach ($cart->product as $cartProduct) {
                    $product = isset($productsById[$cartProduct->id]) ? $productsById[$cartProduct->id] : null;
                    if (!$product) continue;

                    if (!empty($cartProduct->sender['name'])) {
                        $meta = new Model\Order\Meta();
                        $meta->key = 'product.' . $product->ui . '.' . 'sender';
                        $meta->value = $cartProduct->sender['name'];
                    }
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);
            }

            // отправлять смс?
            $isReceiveSms = false;
            if ((bool)$request->data['send_sms']) {
                $isReceiveSms = 1;

                $meta = new Model\Order\Meta();
                $meta->key = 'send_sms';
                $meta->value = 1;
                $metas[] = $meta;
            }

            if (!empty($request->data['user_info']['sms_code'])) {
                $split->user->smsCode = (string)$request->data['user_info']['sms_code'];
            }

            $controllerResponse = (new \EnterAggregator\Controller\Order\Create())->execute(
                $region->id,
                $split,
                $metas,
                $isReceiveSms
            );

            $response->orders = $controllerResponse->orders;
            $response->cart = $cart;
            $response->split = $splitData;
            $response->errors = $controllerResponse->errors;

            // kupivkredit
            try {
                if (!$split->user) {
                    throw new \Exception('Нет данных пользователя');
                }

                /** @var \Enter\Curl\Query[] $orderPutQueries */
                $orderPutQueries = [];
                foreach ($response->orders as $order) {
                    $hasCredit = false;
                    foreach ($order->paymentMethods as $paymentMethod) {
                        if ($paymentMethod->isCredit) {
                            $hasCredit = true;
                            break;
                        }
                    }

                    if ($hasCredit) {
                        $user = new Model\User();
                        $user->firstName = $split->user->firstName;
                        $user->lastName = $split->user->lastName;
                        $user->email = $split->user->email;
                        $user->phone = $split->user->phone;

                        $orderPutQuery = (new Query\Kupivkredit\PutOrder($order, $user, $controllerResponse->productsById));
                        $curl->prepare($orderPutQuery);
                        $orderPutQueries[] = $orderPutQuery;
                    }
                }

                if ((bool)$orderPutQueries) {
                    $curl->execute();

                    foreach ($orderPutQueries as $orderPutQuery) {
                        try {
                            $orderPutQuery->getResult();
                        } catch (\Exception $e) {}
                    }
                }
            } catch(\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order', 'credit']]);
            }

            // response
            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterTerminal\Controller\Order\Create {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $errors = [];
        /** @var Model\Order[] */
        public $orders = [];
        /** @var Model\Cart */
        public $cart;
        /** @var array */
        public $split;
    }
}