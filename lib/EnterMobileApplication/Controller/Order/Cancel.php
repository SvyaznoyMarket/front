<?php

namespace EnterMobileApplication\Controller\Order {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;

    class Cancel {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            if (empty($request->query['clientId'])) {
                throw new \Exception('Не указан параметр clientId', Http\Response::STATUS_BAD_REQUEST);
            }

            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$userAuthToken) {
                throw new \Exception('Не указан параметр token', Http\Response::STATUS_BAD_REQUEST);
            }

            $orderId = is_scalar($request->query['orderId']) ? (string)$request->query['orderId'] : null;
            if (!$orderId) {
                throw new \Exception('Не указан параметр orderId', Http\Response::STATUS_BAD_REQUEST);
            }

            $cancelOrderQuery = new Query\Order\Cancel($orderId, $userAuthToken);
            $cancelOrderQuery->setTimeout(30);
            $curl->query($cancelOrderQuery);

            $result = $cancelOrderQuery->getResult();
            if (empty($result['success'])) {
                if ($config->debugLevel) {
                    $message = 'CORE: ' . (isset($result['message']) ? $result['message'] : '');
                } else {
                    $message = 'Не удалось создать заявку на отмену заказа';
                }

                throw new \Exception($message, Http\Response::STATUS_INTERNAL_SERVER_ERROR);
            }

            return new Http\JsonResponse([]);
        }
    }
}