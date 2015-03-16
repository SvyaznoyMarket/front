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
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $userToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            // ид товара
            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId', Http\Response::STATUS_BAD_REQUEST);
            }

            $context = new Context();
            $context->mainMenu = false;
            $context->favourite = true;
            $controllerResponse = (new \EnterAggregator\Controller\ProductCard())->execute(
                $regionId,
                ['id' => $productId],
                $context,
                $userToken
            );
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