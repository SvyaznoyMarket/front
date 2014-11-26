<?php

namespace EnterTerminal\Controller\Cart {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller;
    use EnterTerminal\Controller\Cart\Split\Response;

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

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            if (empty($request->data['cart']['products'][0]['id'])) {
                throw new \Exception('Не передан параметр cart.products[0].id');
            }

            $cart = new Model\Cart();
            foreach ($request->data['cart']['products'] as $productItem) {
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
                $this->getLogger()->push(['type' => 'warn', 'message' => 'Магазин не найден', 'shopId' => $shopId, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
            }

            // запрос региона
            $regionItemQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionItemQuery);

            // запрос на разбиение корзины
            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                new Model\Region(['id' => $regionId]),
                $shop
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
                'product_list' => array_map(function(Model\Cart\Product $cartProduct) { return [
                    'id'        => $cartProduct->id,
                    'quantity'  => $cartProduct->quantity,
                    'meta_data' => $cartProduct->sender ? ['sender' => $cartProduct->sender] : [],
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

namespace EnterTerminal\Controller\Cart\Split {
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