<?php

namespace EnterMobileApplication\Controller\Cart {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Controller\Cart\Split\Response;

    class Split {
        use ConfigTrait, SessionTrait;

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
            $response = new Response();

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // ид магазина
            $shopId = is_scalar($request->query['shopId']) ? (string)$request->query['shopId'] : null;

            // изменения
            $changeData = $request->data['change'] ?: null;

            // данные о корзине
            if (empty($request->data['cart']['products'][0]['id'])) {
                throw new \Exception('Не передан параметр cart.products[0].id', Http\Response::STATUS_BAD_REQUEST);
            }

            // предыдущее разбиение
            $previousSplitData = null;
            if ($changeData) {
                $previousSplitData = $session->get($config->order->splitSessionKey);
            }

            $cart = new Model\Cart();
            foreach ($request->data['cart']['products'] as $productItem) {
                $cartProduct = new Model\Cart\Product($productItem);
                $cartRepository->setProductForObject($cart, $cartProduct);
            }

            // контроллер
            $controller = new \EnterAggregator\Controller\Cart\Split();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->regionId = $regionId;
            $controllerRequest->shopId = $shopId;
            $controllerRequest->changeData = $changeData;
            $controllerRequest->previousSplitData = $previousSplitData;
            $controllerRequest->cart = $cart;
            // при получении данных о разбиении корзины - записать их в сессию немедленно
            $controllerRequest->splitReceivedSuccessfullyCallback->handler = function() use (&$controllerRequest, &$config, &$session) {
                $session->set($config->order->splitSessionKey, $controllerRequest->splitReceivedSuccessfullyCallback->splitData);
            };
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            // MAPI-25
            $this->setPointImageUrls($controllerResponse->split->pointGroups);

            $response->errors = $controllerResponse->errors;
            $response->split = $controllerResponse->split;

            // response
            return new Http\JsonResponse($response);
        }

        /**
         * @param Model\Cart\Split\PointGroup[] $pointGroups
         */
        private function setPointImageUrls($pointGroups) {
            foreach ($pointGroups as $pointGroup) {
                $image = (new \EnterRepository\Cart())->getPointImageUrl($pointGroup->token);

                if ($image) {
                    $pointGroup->imageUrl = $image;
                }
            }
        }
    }
}

namespace EnterMobileApplication\Controller\Cart\Split {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $errors = [];
        /** @var Model\Cart\Split */
        public $split;
    }
}