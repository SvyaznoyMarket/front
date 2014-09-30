<?php

namespace EnterMobileApplication\Controller\Order {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Controller\Order\Create\Response;

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

            // ответ
            $response = new Response();

            // данные пользователя
            $userData = (array)(isset($request->data['user']) ? $request->data['user'] : []);

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            // запрос региона
            $regionItemQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionItemQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionItemQuery);
            if (!$region) {
                throw new \Exception(sprintf('Регион #%s не найден', $regionId));
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
            $splitData['user'] = array_merge((array)(isset($splitData['user']) ? $splitData['user'] : []), $userData);

            $split = null;
            try {
                $split = new Model\Cart\Split($splitData);
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);

                throw new \Exception('Неверные данные для разбиения корзины. ' . $e->getMessage());
            }

            // дополнительные свойства разбиения
            $split->region = $region;
            $split->clientIp = $request->getClientIp();

            // meta
            $metas = [];

            $controllerResponse = (new \EnterAggregator\Controller\Order\Create())->execute(
                $region->id,
                $split,
                $metas
            );

            $response->orders = $controllerResponse->orders;
            $response->cart = $cart;
            $response->split = $splitData;
            $response->errors = $controllerResponse->errors;

            // response
            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterMobileApplication\Controller\Order\Create {
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