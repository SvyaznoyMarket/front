<?php

namespace EnterTerminal\Controller\Cart\Split {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller;
    use EnterTerminal\Controller\Cart\Split\Update\Response;

    class Update {
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

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $change = (array)$request->data['change'];
            if (!$change) {
                throw new \Exception('Пустой параметр change', Http\Response::STATUS_BAD_REQUEST);
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

            // ид магазина
            $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request); // FIXME

            // запрос магазина
            $shopItemQuery = null;
            if ($shopId) {
                $shopItemQuery = new Query\Shop\GetItemById($shopId);
                $curl->prepare($shopItemQuery);
            }

            $curl->execute();

            // магазин
            $shop = $shopItemQuery ? (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery) : null;
            if ($shopId && !$shop) {
                throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
            }

            // запрос региона
            $regionItemQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionItemQuery);

            // запрос на разбиение корзины
            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                new Model\Region(['id' => $regionId]),
                $shop,
                null,
                $splitData,
                $change
            );
            $splitQuery->setTimeout($config->coreService->timeout * 3);
            $curl->prepare($splitQuery);

            $curl->execute();

            // регион
            $region = null;
            try {
                $region = (new \EnterRepository\Region())->getObjectByQuery($regionItemQuery);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['critical', 'cart.split', 'controller']]);
            }

            // разбиение
            $splitData = [];
            try {
                $splitData = $splitQuery->getResult();
            } catch (Query\CoreQueryException $e) {
                $response->errors = $orderRepository->getErrorList($e);
            }

            // добавление данных о корзине
            $splitData['cart'] = [
                'product_list' => array_map(function(Model\Cart\Product $product) { return [
                    'id'       => $product->id,
                    'quantity' => $product->quantity,
                ]; }, $cart->product),
            ];

            // добавление региона
            if ($region) {
                $response->region = $region;
            }

            // сохранение в сессии
            $session->set($config->order->splitSessionKey, $splitData);

            $response->split = $splitData;

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Cart\Split\Update {
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