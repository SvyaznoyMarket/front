<?php

namespace EnterTerminal\Controller\Order {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
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
            $logger = $this->getLogger();
            $curl = $this->getCurl();
            $session = $this->getSession();
            $cartRepository = new \EnterRepository\Cart();
            $orderRepository = new \EnterRepository\Order();

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

            $controllerResponse = (new \EnterAggregator\Controller\Order\Create())->execute(
                $shop->regionId,
                $split
            );

            if (!(bool)$controllerResponse->orders) {
                throw new \Exception('Не удалось создать заказ');
            }

            $response->orders = $controllerResponse->orders;
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
        /** @var Model\Order[] */
        public $errors = [];
        /** @var array */
        public $orders = [];
        /** @var Model\Cart */
        public $cart;
        /** @var array */
        public $split;
    }
}