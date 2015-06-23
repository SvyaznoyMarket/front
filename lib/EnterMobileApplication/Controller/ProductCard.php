<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;

    class ProductCard {
        use ProductListingTrait;
        
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

            return new Http\JsonResponse([
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
                'media' => $controllerResponse->product->media,
                'rating' => $controllerResponse->product->rating ? [
                    'score'       => $controllerResponse->product->rating->score,
                    'starScore'   => $controllerResponse->product->rating->starScore,
                    'reviewCount' => $controllerResponse->product->rating->reviewCount,
                ] : null,
                'model' => $controllerResponse->product->model,
                'line' => $controllerResponse->product->line,
                'nearestDeliveries' => $controllerResponse->product->nearestDeliveries,
                'accessoryIds' => $controllerResponse->product->accessoryIds,
                'relatedIds' => $controllerResponse->product->relatedIds,
                'relation' => [
                    'accessories' => $this->getProductList($controllerResponse->product->relation->accessories),
                    'similar' => $this->getProductList($controllerResponse->product->relation->similar),
                ],
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
            ]);
        }
    }
}