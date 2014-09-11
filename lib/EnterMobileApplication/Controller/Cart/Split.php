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

            if (empty($request->data['cart']['products'][0]['id'])) {
                throw new \Exception('Не передан параметр cart.products[0].id');
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
                new Model\Region(['id' => $region->id])
            );
            $splitQuery->setTimeout($config->coreService->timeout * 2);
            $curl->prepare($splitQuery);

            $curl->execute();

            // разбиение
            $splitData = [];
            try {
                $splitData = $splitQuery->getResult();
            } catch (Query\CoreQueryException $e) {
                $response->errors = $orderRepository->getErrorList($e);
            }

            // добавление данных о корзине
            $splitData['cart'] = [
                'product_list' => array_map(function(Model\Cart\Product $product) { return ['id' => $product->id, 'quantity' => $product->quantity]; }, $cart->product),
            ];

            // сохранение в сессии
            $session->set($config->order->splitSessionKey, $splitData);

            $response->split = new Model\Cart\Split($splitData);

            // response
            return new Http\JsonResponse($response);
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