<?php

namespace EnterTerminal\Controller\Order {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;
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
            $orderRepository = new Repository\Order();

            // ответ
            $response = new Response();

            // данные пользователя
            $userData = (array)$request->data['user_info'];

            // ид магазина
            $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request); // FIXME

            // запрос магазина
            $shopItemQuery = new Query\Shop\GetItemById($shopId);
            $curl->prepare($shopItemQuery);

            $curl->execute();

            // магазин
            $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);
            if (!$shop) {
                throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
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
            $split->region = $shop->region;
            $split->clientIp = $request->getClientIp();

            // создание заказа
            $createOrderQuery = $orderRepository->getPacketQueryBySplit($split);
            if (!$createOrderQuery) {
                throw new \Exception('Не удалось создать запрос на создание заказа');
            }

            $curl->query($createOrderQuery);

            $order = null;
            try {
                $order = $createOrderQuery->getResult();
            } catch (Query\CoreQueryException $e) {
                $response->errors = $orderRepository->getErrorList($e);
            }

            $response->order = $order;
            $response->cart = $cart;
            $response->split = $splitData;

            // response
            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterTerminal\Controller\Order\Create {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $order;
        /** @var Model\Cart */
        public $cart;
        /** @var array */
        public $split;
        /** @var array */
        public $errors = [];
    }
}