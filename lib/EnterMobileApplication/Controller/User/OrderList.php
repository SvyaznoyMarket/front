<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\OrderList\Response;

    class OrderList {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token');
            }

            try {
                $orderListQuery = new Query\Order\GetListByUserToken($token);
                $curl->prepare($orderListQuery);

                $curl->execute();

                $orders = (new \EnterRepository\Order())->getObjectListByQuery($orderListQuery);

                // магазин
                $shopsById = [];
                foreach ($orders as $order) {
                    if (!$order->shopId) continue;
                    $shopsById[$order->shopId] = null;
                }

                try {
                    if ((bool)$shopsById) {
                        $shopRepository = new \EnterRepository\Shop();

                        $shopListQuery = new Query\Shop\GetListByIdList(array_keys($shopsById));
                        $curl->prepare($shopListQuery)->execute();

                        $shopsById = $shopRepository->getIndexedObjectListByQuery($shopListQuery, function(&$item) {
                            // TODO
                        });
                        foreach ($orders as $order) {
                            $shop = ($order->shopId && isset($shopsById[$order->shopId])) ? $shopsById[$order->shopId] : null;
                            if (!$shop) continue;

                            $order->shop = $shop;
                        }
                    }
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
                }

                $response->orders = $orders;
                $response->token = $token;
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                $response->token = null;
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\OrderList {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var Model\Order[] */
        public $orders = [];
    }
}