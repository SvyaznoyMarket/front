<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller;
    use EnterTerminal\Controller\ProductCard\Response;

    class ProductCard {
        use CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

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
            $controllerRequest->config->delivery = false; // TERMINALS-971
            $controllerRequest->config->authorizedEvent = false; // SITE-5487
            $controllerRequest->regionId = $regionId;
            $controllerRequest->productCriteria = ['id' => $productId];
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
                'kitCount' => $controllerResponse->product->kitCount,
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
                            'unit' => '',
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
                'nearestDeliveries' => $controllerResponse->product->deliveries,
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
                'isStore' => $controllerResponse->product->isStore,
                'storeLabel' => $controllerResponse->product->storeLabel,
                'assemblingLabel' => $controllerResponse->product->assemblingLabel,
            ] : null;
            $response->reviews = $controllerResponse->product ? $controllerResponse->product->reviews : []; // FIXME: удалить
            $response->kitProducts = $controllerResponse->product ? $controllerResponse->product->relation->kits : []; // FIXME: удалить

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\ProductCard {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product */
        public $product;
        /** @var Model\Product\Review[] */
        public $reviews = [];
        /** @var Model\Product[] */
        public $kitProducts = [];
    }
}