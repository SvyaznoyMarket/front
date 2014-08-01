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

            // корзина из сессии
            $cart = $cartRepository->getObjectByHttpSession($session);

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

            $split = null;
            try {
                $split = new Model\Cart\Split($splitData);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);
            }

            if (!$split) {
                throw new \Exception('Неверные данные для разбиения корзины');
            }

            // дополнительные свойства разбиения
            $split->clientIp = $request->getClientIp();

            // создание заказа
            $createOrderQuery = (new Repository\Order())->getPacketQueryBySplit($split);
            if (!$createOrderQuery) {
                throw new \Exception('Не удалось создать запрос на создание заказа');
            }

            $curl->query($createOrderQuery);

            try {
                $createOrderQuery->getResult();
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);
            }

            // ответ
            $response = new Response();

            $response->cart = $cart;
            $response->split = $splitData;

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Order\Create {
    use EnterModel as Model;

    class Response {
        /** @var Model\Cart */
        public $cart;
        /** @var array */
        public $split;
    }
}