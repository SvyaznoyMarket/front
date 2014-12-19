<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterAggregator\Model\Context\ProductCard as Context;
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

            $context = new Context();
            $context->mainMenu = false;
            $context->delivery = false; // TERMINALS-971
            $controllerResponse = (new \EnterAggregator\Controller\ProductCard())->execute(
                $regionId,
                ['id' => $productId],
                $context
            );
            // товар
            if (!$controllerResponse->product) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Товар #%s не найден', $productId));
            }

            // ответ
            $response = new Response();
            $response->catalogConfig = $controllerResponse->catalogConfig;
            $response->product = $controllerResponse->product;
            $response->reviews = $controllerResponse->product ? $controllerResponse->product->reviews : []; // FIXME: удалить
            $response->kitProducts = $controllerResponse->product ? $controllerResponse->product->relation->kits : []; // FIXME: удалить

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\ProductCard {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Catalog\Config */
        public $catalogConfig;
        /** @var Model\Product */
        public $product;
        /** @var Model\Product\Review[] */
        public $reviews = [];
        /** @var Model\Product[] */
        public $kitProducts = [];
    }
}