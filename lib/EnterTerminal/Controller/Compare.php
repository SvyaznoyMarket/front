<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\Compare\Response;

    class Compare {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $session = $this->getSession();
            $curl = $this->getCurl();
            $productRepository = new \EnterRepository\Product();
            $compareRepository = new \EnterRepository\Compare();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // сравнение из сессии
            $compare = $compareRepository->getObjectByHttpSession($session);

            $productsById = [];
            foreach ($compare->product as $compareProduct) {
                $productsById[$compareProduct->id] = null;
            }

            $descriptionListQuery = null;
            $productListQuery = null;
            if ((bool)$productsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $regionId);
                $curl->prepare($productListQuery);

                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    array_keys($productsById),
                    [
                        'media'       => true,
                        'media_types' => ['main'], // только главная картинка
                    ]
                );
                $curl->prepare($descriptionListQuery);
            }

            $curl->execute();

            if ($productListQuery) {
                $productsById = $productRepository->getIndexedObjectListByQueryList([$productListQuery], function(&$item) {
                    // оптимизация
                    if ($mediaItem = reset($item['media'])) {
                        $item['media'] = [$mediaItem];
                    }
                });

                // товары по ui
                $productsByUi = [];
                call_user_func(function() use (&$productsById, &$productsByUi) {
                    foreach ($productsById as $product) {
                        $productsByUi[$product->ui] = $product;
                    }
                });

                // медиа для товаров
                if ($productsByUi && $descriptionListQuery) {
                    $productRepository->setDescriptionForListByListQuery($productsByUi, $descriptionListQuery);
                }
            }

            // список магазинов, в которых есть товар
            try {
                $shopIds = [];
                foreach ($productsById as $product) {
                    foreach ($product->stock as $stock) {
                        if (!$stock->shopId) continue;

                        $shopIds[] = $stock->shopId;
                    }
                }
                if ((bool)$shopIds) {
                    $shopListQuery = new Query\Shop\GetListByIdList($shopIds);
                    $curl->prepare($shopListQuery);

                    $curl->execute();

                    foreach ($productsById as $product) {
                        $shopStatesByShopId = [];
                        foreach ($product->stock as $stock) {
                            if ($stock->shopId && (($stock->showroomQuantity + $stock->quantity) > 0)) {
                                $shopState = new Model\Product\ShopState();
                                $shopState->quantity = $stock->quantity;
                                $shopState->showroomQuantity = $stock->showroomQuantity;
                                $shopState->isInShowroomOnly = !$shopState->quantity && ($shopState->showroomQuantity > 0);

                                $shopStatesByShopId[$stock->shopId] = $shopState;
                            }
                        }
                        if ((bool)$shopStatesByShopId) {
                            $productRepository->setShopStateForObjectListByQuery([$product->id => $product], $shopStatesByShopId, $shopListQuery);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            // сравнение свойств товара
            $compareRepository->compareProductObjectList($compare, $productsById);

            // ответ
            $response = new Response();
            $response->groups = $compareRepository->getGroupListByObject($compare, $productsById);
            foreach ($compare->product as $compareProduct) {
                $product = !empty($productsById[$compareProduct->id])
                    ? $productsById[$compareProduct->id]
                    : new Model\Product([
                        'id' => $compareProduct->id,
                    ]);

                $response->products[] = $product;
            }

            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Compare {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product[] */
        public $groups = [];
        /** @var Model\Product[] */
        public $products = [];
    }
}