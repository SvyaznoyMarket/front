<?php

namespace EnterMobileApplication\Controller\Cart {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterModel as Model;
    use EnterQuery as Query;
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
            $cartRepository = new \EnterRepository\Cart();
            $helper = new \Enter\Helper\Template();

            $session = $this->getSession();

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

            // корзина
            $cart = new Model\Cart();
            foreach ($request->data['cart']['products'] as $productItem) {
                $cartProduct = new Model\Cart\Product($productItem);
                $cartRepository->setProductForObject($cart, $cartProduct);
            }

            // бонусные карты
            $bonusCardData = call_user_func(function() use (&$request, &$changeData) {
                $bonusCardData = [];

                if (isset($request->data['user']['bonusCards'][0])) {
                    $bonusCardData = $request->data['user']['bonusCards'];
                } else if (isset($changeData['user']['bonusCards'][0])) {
                    $bonusCardData = $changeData['user']['bonusCards'];
                }

                foreach ($bonusCardData as $i => $cardItem) {
                    if (!isset($cardItem['type']) || !isset($cardItem['number'])) {
                        unset($bonusCardData[$i]);
                    }
                }

                return $bonusCardData;
            });
            if ($bonusCardData) {
                $session->set($config->order->bonusCardSessionKey, $bonusCardData);
            }

            // контроллер
            $controller = new \EnterAggregator\Controller\Cart\Split();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->regionId = $regionId;
            $controllerRequest->shopId = $shopId;
            $controllerRequest->changeData = (new \EnterRepository\Cart())->dumpSplitChange($changeData, $previousSplitData);
            $controllerRequest->previousSplitData = $previousSplitData;
            $controllerRequest->disableOnlinePaymentMethods = true;
            $controllerRequest->cart = $cart;
            // при получении данных о разбиении корзины - записать их в сессию немедленно
            $controllerRequest->splitReceivedSuccessfullyCallback->handler = function() use (&$controllerRequest, &$config, &$session) {
                $session->set($config->order->splitSessionKey, $controllerRequest->splitReceivedSuccessfullyCallback->splitData);
            };
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            $this->correctPoints($controllerResponse->split->pointGroups);

            $response->errors = $controllerResponse->errors;
            $response->split = $controllerResponse->split;

            // type fix
            foreach ($response->split->orders as $order) {
                if (!(bool)$order->groupedPossiblePointIds) {
                    $order->groupedPossiblePointIds = null;
                }

                $order->possiblePaymentMethodIds = array_values($order->possiblePaymentMethodIds);

                // MAPI-116
                foreach ($order->products as $product) {
                    $product->webName = $helper->unescape($product->webName);
                    $product->namePrefix = $helper->unescape($product->namePrefix);
                    $product->name = $helper->unescape($product->name);
                }

                // MAPI-163
                if ($order->delivery && !$order->possibleDays) {
                    $order->delivery->date = null;
                }
            }

            // response
            return new Http\JsonResponse($response);
        }

        /**
         * @param Model\Cart\Split\PointGroup[] $pointGroups
         */
        private function correctPoints($pointGroups) {
            $pointRepository = new \EnterMobileApplication\Repository\Point();
            
            foreach ($pointGroups as $pointGroup) {
                // MAPI-78
                $pointGroup->blockName = $pointRepository->getName($pointGroup->id, $pointGroup->blockName);

                // MAPI-25
                $pointGroup->media = $pointRepository->getMedia($pointGroup->token);
                foreach ($pointGroup->media->photos as $media) {
                    if (in_array('logo', $media->tags, true)) {
                        foreach ($media->sources as $source) {
                            if ($source->type === '100x100') {
                                $pointGroup->imageUrl = $source->url; // TODO MAPI-61 Удалить элементы pointGroups.<int>.imageUrl и pointGroups.<int>.markerUrl из ответа метода Cart/Split
                            }
                        }
                    }

                    if (in_array('marker', $media->tags, true)) {
                        foreach ($media->sources as $source) {
                            if ($source->type === '61x80') {
                                $pointGroup->markerUrl = $source->url; // TODO MAPI-61 Удалить элементы pointGroups.<int>.imageUrl и pointGroups.<int>.markerUrl из ответа метода Cart/Split
                            }
                        }
                    }
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