<?php

namespace EnterTerminal\Controller\Product {


    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller;
    use EnterTerminal\Controller\Product\Delivery\Response;

    class Delivery {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            // ид товара
            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId');
            }

            // запрос доставки товара
            $cartProducts = [];
            $cartProducts[] = new Model\Cart\Product(['id' => $productId, 'quantity' => 1]);

            $deliveryListQuery = new Query\Product\Delivery\GetListByCartProductList($cartProducts, $regionId);
            $deliveryListQuery->setTimeout(2.5 * $config->coreService->timeout);
            $curl->prepare($deliveryListQuery);

            $curl->execute();

            // ответ
            $response = new Response();
            $response->nearestDeliveries = (new \EnterRepository\Product())->getDeliveryObjectByListQuery($deliveryListQuery);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Product\Delivery {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\NearestDelivery[] */
        public $nearestDeliveries = [];
    }
}
