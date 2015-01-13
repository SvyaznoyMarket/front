<?php

namespace EnterTerminal\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterAggregator\Model\Context\Product\RecommendedList as Context;
    use EnterTerminal\Controller\Product\RecommendedList\Response;

    class RecommendedList {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $productIds = (array)$request->query['productIds'];
            if (!(bool)$productIds) {
                throw new \Exception('Не указан параметр productIds', Http\Response::STATUS_BAD_REQUEST);
            }

            $types = (array)$request->query['types'];

            $response = new Response();

            $context = new Context();
            $context->alsoBought = in_array('alsoBought', $types);
            $context->alsoViewed = in_array('alsoViewed', $types);
            $context->similar = in_array('similar', $types);

            $controllerResponse = (new \EnterAggregator\Controller\Product\RecommendedListByProduct())->execute(
                $regionId,
                $productIds,
                $context
            );

            foreach ($controllerResponse->alsoBoughtIdList as $iProductId) {
                /** @var Model\Product|null $product */
                $product = isset($controllerResponse->recommendedProductsById[$iProductId]) ? $controllerResponse->recommendedProductsById[$iProductId] : null;
                if (!$product) continue;

                $product->sender = [
                    'name' => 'retailrocket',
                ];

                $response->recommendedProducts['alsoBought'][] = $product;
            }

            foreach ($controllerResponse->alsoViewedIdList as $iProductId) {
                /** @var Model\Product|null $product */
                $product = isset($controllerResponse->recommendedProductsById[$iProductId]) ? $controllerResponse->recommendedProductsById[$iProductId] : null;
                if (!$product) continue;

                $product->sender = [
                    'name' => 'retailrocket',
                ];

                $response->recommendedProducts['alsoViewed'][] = $product;
            }

            foreach ($controllerResponse->similarIdList as $iProductId) {
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

namespace EnterTerminal\Controller\Product\RecommendedList {
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
