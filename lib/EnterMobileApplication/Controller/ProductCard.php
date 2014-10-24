<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\Model\Context\ProductCard as Context;
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
                throw new \Exception('Не указан параметр regionId');
            }

            // ид товара
            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId');
            }

            $context = new Context();
            $context->mainMenu = false;
            $controllerResponse = (new \EnterAggregator\Controller\ProductCard())->execute($regionId, ['id' => $productId], $context);
            // товар
            if (!$controllerResponse->product) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Товар #%s не найден', $productId));
            }

            // ответ
            $response = new Response();
            $response->product = $controllerResponse->product;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\ProductCard {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product|null */
        public $product;
    }
}