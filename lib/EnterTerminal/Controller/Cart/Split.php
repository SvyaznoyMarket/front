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
            $shopItemQuery = new Query\Shop\GetItemById($shopId);
            $curl->prepare($shopItemQuery);

            $curl->execute();

            // магазин
            $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);
            if (!$shop) {
                throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
            }

            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                $shop->regionId,
                $shop
            );
            $splitQuery->setTimeout($config->coreService->timeout * 2);
            $curl->prepare($splitQuery);

            $curl->execute();

            // разбиение
            $splitData = $splitQuery->getResult();
            // сохранение в сессии
            $session->set($config->order->splitSessionKey, $splitData);

            // ответ
            $response = new Response();

            $response->cart = $cart;
            $response->split = $splitData;

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Cart\Split {
    use EnterModel as Model;

    class Response {
        /** @var Model\Cart */
        public $cart;
        /** @var array */
        public $split;
    }
}