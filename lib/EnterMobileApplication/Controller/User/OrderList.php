<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;

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
            
            $response = [];

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
            }

            // количество товаров на страницу
            $limit = (int)$request->query['limit'] ?: 20;
            if ($limit < 1) {
                throw new \Exception('Параметр limit не должен быть меньше 1', Http\Response::STATUS_BAD_REQUEST);
            }
            if ($limit > 40) {
                throw new \Exception('Параметр limit не должен быть больше 40', Http\Response::STATUS_BAD_REQUEST);
            }

            // номер страницы
            $pageNum = (int)$request->query['page'] ?: 1;
            if ($limit < 1) {
                throw new \Exception('Параметр page не должен быть меньше 1', Http\Response::STATUS_BAD_REQUEST);
            }
            $offset = ($pageNum - 1) * $limit;

            try {
                $orderListQuery = new Query\Order\GetListByUserToken($token, $offset, $limit);
                $orderListQuery->setTimeout(6 * $config->coreService->timeout);
                $curl->prepare($orderListQuery);

                $curl->execute();

                //$orders = (new \EnterRepository\Order())->getObjectListByQuery($orderListQuery);
                $orders = [];
                try {
                    $result = (array)$orderListQuery->getResult() + ['orders' => [], 'total' => null];

                    foreach ($result['orders'] as $item) {
                        if (!isset($item['number'])) continue;

                        $orders[] = new Model\Order($item);
                    }
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
                }

                $response['orders'] = array_map(function(Model\Order $order) {
                    return [
                        'id' => $order->id,
                        'accessToken' => $order->token,
                        'numberErp' => $order->numberErp,
                        'createdAt' => $order->createdAt,
                        'paySum' => $order->paySum,
                    ];
                }, $orders);
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}