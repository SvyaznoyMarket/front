<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Controller\ProductCard\Response;

    class ProductCard {
        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $userToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

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
            $controllerRequest->userToken = $userToken;
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            // товар
            if (!$controllerResponse->product) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Товар #%s не найден', $productId));
            }

            // ответ
            $response = new Response();
            $response->product = $controllerResponse->product ? [
                'id' => $controllerResponse->product->id,
                'ui' => $controllerResponse->product->ui,
                'wikimartId' => $controllerResponse->product->wikimartId,
                'article' => $controllerResponse->product->article,
                'barcode' => $controllerResponse->product->barcode,
                'typeId' => $controllerResponse->product->typeId,
                'webName' => $controllerResponse->product->webName,
                'namePrefix' => $controllerResponse->product->namePrefix,
                'name' => $controllerResponse->product->name,
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
                'category' => $controllerResponse->product->category,
                'brand' => $controllerResponse->product->brand,
                'properties' => $controllerResponse->product->properties,
                'propertyGroups' => $controllerResponse->product->propertyGroups,
                'stock' => $controllerResponse->product->stock,
                'shopStates' => $controllerResponse->product->shopStates,
                'price' => $controllerResponse->product->price,
                'oldPrice' => $controllerResponse->product->oldPrice,
                'labels' => $controllerResponse->product->labels,
                'media' => $controllerResponse->product->media,
                'rating' => $controllerResponse->product->rating,
                'model' => $controllerResponse->product->model ? [
                    'properties' => $controllerResponse->product->model->property ? [
                        [
                            'id' => $controllerResponse->product->model->property->id,
                            'name' => $controllerResponse->product->model->property->name,
                            'unit' => null,
                            'isImage' => false,
                            'options' => array_map(function(\EnterModel\Product\ProductModel\Property\Option $option) {
                                return [
                                    'value' => $option->value,
                                    'product' => $option->product ? [
                                        'id' => $option->product->id,
                                        'name' => $option->product->name,
                                        'link' => $option->product->link,
                                        'token' => $option->product->token,
                                        'image' => '',
                                    ] : null,
                                    'shownValue' => $option->value,
                                ];
                            }, $controllerResponse->product->model->property->options),
                        ]
                    ] : [],
                ] : null,
                'line' => $controllerResponse->product->line,
                'nearestDeliveries' => $controllerResponse->product->nearestDeliveries,
                'accessoryIds' => $controllerResponse->product->accessoryIds,
                'relatedIds' => $controllerResponse->product->relatedIds,
                'relation' => $controllerResponse->product->relation,
                'kit' => $controllerResponse->product->kit,
                'reviews' => $controllerResponse->product->reviews,
                'trustfactors' => $controllerResponse->product->trustfactors,
                'partnerOffers' => $controllerResponse->product->partnerOffers,
                'availableStoreQuantity' => $controllerResponse->product->availableStoreQuantity,
                'favorite' => $controllerResponse->product->favorite,
                'sender' => $controllerResponse->product->sender,
                'ga' => $controllerResponse->product->ga,
            ] : null;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\ProductCard {
    use EnterModel as Model;

    class Response {
        /** @var array|null */
        public $product;
    }
}