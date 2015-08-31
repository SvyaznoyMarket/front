<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\SessionTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;

    class ProductCard {
        use ProductListingTrait, SessionTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $session = $this->getSession();
            $helper = new \Enter\Helper\Template();
            $productRepository = new \EnterMobileApplication\Repository\Product();

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            // ид товара
            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId', Http\Response::STATUS_BAD_REQUEST);
            }

            // контроллер
            $controller = new \EnterAggregator\Controller\ProductCard();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->config->mainMenu = false;
            $controllerRequest->config->favourite = true;
            $controllerRequest->regionId = $regionId;
            $controllerRequest->productCriteria = ['id' => $productId];
            $controllerRequest->userToken = $userAuthToken;
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            // товар
            if (!$controllerResponse->product) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Товар #%s не найден', $productId));
            }

            $productRepository->setViewedProductIdToSession($controllerResponse->product->id, $session);

            // MAPI-76 Получение данных в едином формате
            call_user_func(function() use(&$controllerResponse) {
                if ($controllerResponse->product->model) {
                    /** @var Model\Product\Property[] $propertiesById */
                    $propertiesById = [];
                    foreach ($controllerResponse->product->properties as $property) {
                        $propertiesById[$property->id] = $property;
                    }

                    foreach ($controllerResponse->product->model->properties as $modelProperty) {
                        if (isset($propertiesById[$modelProperty->id])) {
                            $property = $propertiesById[$modelProperty->id];
                            foreach ($modelProperty->options as $modelOption) {
                                foreach ($property->options as $option) {
                                    if (preg_replace('/^(\d+)\.(\d+)$/', '$1,$2', $modelOption->value) === $option->value) {
                                        $modelOption->value = $option->value;
                                        $modelOption->shownValue = $option->value;
                                        break (3);
                                    }
                                }
                            }
                        }
                    }
                }
            });

            return new Http\JsonResponse(['product' => [
                'id' => $controllerResponse->product->id,
                'ui' => $controllerResponse->product->ui,
                'article' => $controllerResponse->product->article,
                'barcode' => $controllerResponse->product->barcode,
                'typeId' => $controllerResponse->product->typeId,
                'webName' => $helper->unescape($controllerResponse->product->webName),
                'namePrefix' => $helper->unescape($controllerResponse->product->namePrefix),
                'name' => $helper->unescape($controllerResponse->product->name),
                'token' => $controllerResponse->product->token,
                'link' => $controllerResponse->product->link,
                'description' => $controllerResponse->product->description,
                'tagline' => $controllerResponse->product->tagline,
                'isBuyable' => $controllerResponse->product->isBuyable,
                'isInShopOnly' => $controllerResponse->product->isInShopOnly,
                'isInShopStockOnly' => $controllerResponse->product->isInShopStockOnly,
                'isInShopShowroomOnly' => $controllerResponse->product->isInShopShowroomOnly,
                'isInWarehouse' => $controllerResponse->product->isInWarehouse,
                'isKitLocked' => $controllerResponse->product->isKitLocked,
                'kitCount' => $controllerResponse->product->kitCount,
                'category' => [
                    'id' => $controllerResponse->product->category->id,
                ],
                'brand' => $controllerResponse->product->brand ? [
                    'id'   => $controllerResponse->product->brand->id,
                    'name' => $controllerResponse->product->brand->name,
                ] : null,
                'properties' => $controllerResponse->product->properties,
                'propertyGroups' => $controllerResponse->product->propertyGroups,
                'stock' => $controllerResponse->product->stock,
                'shopStates' => $controllerResponse->product->shopStates,
                'price' => $controllerResponse->product->price,
                'oldPrice' => $controllerResponse->product->oldPrice,
                'labels' => array_map(function(Model\Product\Label $label) {
                    return [
                        'id'    => $label->id,
                        'name'  => $label->name,
                        'media' => $label->media,
                    ];
                }, $controllerResponse->product->labels),
                'media' => $productRepository->getMedia($controllerResponse->product),
                'rating' => $controllerResponse->product->rating ? [
                    'score'       => $controllerResponse->product->rating->score,
                    'starScore'   => $controllerResponse->product->rating->starScore,
                    'reviewCount' => $controllerResponse->product->rating->reviewCount,
                ] : null,
                'model' => $controllerResponse->product->model,
                'line' => $controllerResponse->product->line,
                'nearestDeliveries' => is_array($controllerResponse->product->nearestDeliveries) ? array_map(function(\EnterModel\Product\NearestDelivery $nearestDelivery) {
                    return [
                        'id' => $nearestDelivery->id,
                        'token' => $nearestDelivery->token === 'standart' ? $nearestDelivery->token : 'self', // MAPI-101
                        'productId' => $nearestDelivery->productId,
                        'price' => $nearestDelivery->price,
                        'shopsById' => $nearestDelivery->shopsById,
                        'deliveredAt' => $nearestDelivery->deliveredAt,
                    ];
                }, $controllerResponse->product->nearestDeliveries) : [],
                'accessoryIds' => $controllerResponse->product->accessoryIds,
                'relatedIds' => $controllerResponse->product->relatedIds,
                'relation' => [
                    'accessories' => $this->getProductList($controllerResponse->product->relation->accessories),
                    'similar' => $this->getProductList($controllerResponse->product->relation->similar),
                ],
                'kit' => $controllerResponse->product->kit,
                'reviews' => $controllerResponse->product->reviews,
                'trustfactors' => $controllerResponse->product->trustfactors,
                'partnerOffers' => $productRepository->getPartnerOffers($controllerResponse->product),
                'availableStoreQuantity' => $controllerResponse->product->availableStoreQuantity,
                'favorite' => $controllerResponse->product->favorite,
                'sender' => $controllerResponse->product->sender,
                'ga' => $controllerResponse->product->ga,
                'isStore' => $controllerResponse->product->isStore,
                'storeLabel' => $controllerResponse->product->storeLabel,
            ]]);
        }
    }
}