<?php

namespace EnterMobileApplication\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Product\RecommendedList\Response;

    class RecommendedList {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $productIds = (array)$request->query['productIds'];
            if (!(bool)$productIds) {
                throw new \Exception('Не указан параметр productIds', Http\Response::STATUS_BAD_REQUEST);
            }

            $limit = is_scalar($request->query['limit']) ? (int)$request->query['limit'] : null;
            if (!$limit) {
                throw new \Exception('Не указан параметр limit', Http\Response::STATUS_BAD_REQUEST);
            }

            $types = (array)$request->query['types'];

            $response = new Response();

            // контроллер
            $controller = new \EnterAggregator\Controller\Product\RecommendedListByProduct();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->config->alsoBought = in_array('alsoBought', $types);
            $controllerRequest->config->alsoViewed = in_array('alsoViewed', $types);
            $controllerRequest->config->similar = in_array('similar', $types);
            $controllerRequest->regionId = $regionId;
            $controllerRequest->productIds = $productIds;
            // ответ от контроллера
            $controllerResponse = $controller->execute($controllerRequest);

            foreach (array_slice($controllerResponse->alsoBoughtIdList, 0, $limit) as $i => $iProductId) {
                /** @var Model\Product|null $product */
                $product = isset($controllerResponse->recommendedProductsById[$iProductId]) ? $controllerResponse->recommendedProductsById[$iProductId] : null;
                if (!$product) continue;

                $product->sender = [
                    'name' => 'retailrocket',
                ];

                $response->recommendedProducts['alsoBought'][] = $product;
            }

            foreach (array_slice($controllerResponse->alsoViewedIdList, 0, $limit) as $iProductId) {
                /** @var Model\Product|null $product */
                $product = isset($controllerResponse->recommendedProductsById[$iProductId]) ? $controllerResponse->recommendedProductsById[$iProductId] : null;
                if (!$product) continue;

                $product->sender = [
                    'name' => 'retailrocket',
                ];

                $response->recommendedProducts['alsoViewed'][] = $product;
            }

            foreach (array_slice($controllerResponse->similarIdList, 0, $limit) as $iProductId) {
                /** @var Model\Product|null $product */
                $product = isset($controllerResponse->recommendedProductsById[$iProductId]) ? $controllerResponse->recommendedProductsById[$iProductId] : null;
                if (!$product) continue;

                $product->sender = [
                    'name' => 'retailrocket',
                ];

                $response->recommendedProducts['similar'][] = $product;
            }

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Product\RecommendedList {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product[] */
        public $recommendedProducts = [
            'alsoBought' => [],
            'alsoViewed' => [],
            'similar'    => [],
        ];
    }
}
