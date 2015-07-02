<?php

namespace EnterTerminal\Controller\Cart {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\SessionTrait;
    use EnterModel as Model;
    use EnterQuery as Query;
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
            $cartRepository = new \EnterRepository\Cart();
            
            $session = $this->getSession();

            // ответ
            $response = new Response();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request); // FIXME
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
                } else if ($changeData['user']['bonusCards'][0]) {
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

            $this->formatResponse($response);

            // response
            return new Http\JsonResponse($response);
        }

        /**
         * @param Model\Cart\Split\PointGroup[] $pointGroups
         */
        private function setPointImageUrls($pointGroups) {
            $pointRepository = new \EnterRepository\Point();
            
            foreach ($pointGroups as $pointGroup) {
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

        private function formatResponse(Response $response) {
            $deliveryGroupsById = [];
            foreach ($response->split->deliveryGroups as $deliveryGroup) {
                $deliveryGroupsById[$deliveryGroup->id] = $deliveryGroup;
                //unset($deliveryGroup->id);
            }
            $response->split->deliveryGroups = $deliveryGroupsById;

            $deliveryMethodsByToken = [];
            foreach ($response->split->deliveryMethods as $deliveryMethod) {
                $deliveryMethodsByToken[$deliveryMethod->token] = $deliveryMethod;
                //unset($deliveryMethod->token);
            }
            $response->split->deliveryMethods = $deliveryMethodsByToken;

            if (false) {
                $paymentMethodsById = [];
                foreach ($response->split->paymentMethods as $paymentMethod) {
                    $paymentMethodsById[$paymentMethod->id] = $paymentMethod;
                    //unset($paymentMethod->id);
                }
                $response->split->paymentMethods = $paymentMethodsById;
            }

            /** @var Model\Cart\Split\PointGroup[] $pointGroupsByToken */
            $pointGroupsByToken = [];
            foreach ($response->split->pointGroups as $pointGroup) {
                $pointGroupsByToken[$pointGroup->token] = $pointGroup;

                $pointsById = [];
                foreach ($pointGroup->points as $point) {
                    $pointsById[$point->id] = $point;
                    //unset($point->id);
                }

                $pointGroupsByToken[$pointGroup->token]->points = $pointsById;
                //unset($pointGroup->token);
            }
            $response->split->pointGroups = $pointGroupsByToken;

            /** @var Model\Cart\Split\Order[] $ordersByBlockName */
            $ordersByBlockName = [];
            foreach ($response->split->orders as $order) {
                $order->sum = (float)$order->sum;
                $order->originalSum = (float)$order->originalSum;

                $ordersByBlockName[$order->blockName] = $order;

                $groupedPossiblePointsById = [];
                foreach ($order->possiblePoints as $possiblePoint) {
                    $groupedPossiblePointsById[$possiblePoint->groupToken][$possiblePoint->id] = $possiblePoint;
                    //unset($possiblePoint->id, $possiblePoint->groupToken);
                }
                $ordersByBlockName[$order->blockName]->possiblePoints = $groupedPossiblePointsById;

                $possibleDay = null;
                foreach ($order->possibleDays as &$possibleDay) {
                    $possibleDay = (int)$possibleDay;
                }
                unset($possibleDay);

            }
            $response->split->orders = $ordersByBlockName;
        }
    }
}

namespace EnterTerminal\Controller\Cart\Split {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $errors = [];
        /** @var Model\Cart\Split */
        public $split;
    }
}