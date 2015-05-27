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

    class Update {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $session = $this->getSession();
            $cartRepository = new \EnterRepository\Cart();

            // ответ
            $response = new \EnterTerminal\Model\ControllerResponse\Cart\Split();

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

            $controller = new \EnterAggregator\Controller\Cart\Split();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->regionId = $regionId;
            $controllerRequest->shopId = $shopId;
            $controllerRequest->changeData = $change;
            $controllerRequest->previousSplitData = $splitData;
            $controllerRequest->cart = $cart;
            // при получении данных о разбиении корзины - записать их в сессию немедленно
            $controllerRequest->splitReceivedSuccessfullyCallback->handler = function() use (&$controllerRequest, &$config, &$session, &$response) {
                $session->set($config->order->splitSessionKey, $controllerRequest->splitReceivedSuccessfullyCallback->splitData);

                // Терминалы пока используют сырые данные, не изменённые моделями API агрегатора
                $response->split = $controllerRequest->splitReceivedSuccessfullyCallback->splitData;
            };
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            $response->errors = $controllerResponse->errors;
            $response->region = $controllerResponse->region;

            (new \EnterTerminal\Repository\Cart\Split)->correctResponse($response, $controllerResponse->split);

            // response
            return new Http\JsonResponse($response);
        }
    }
}