<?php

namespace EnterTerminal\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterTerminal\Controller\Product\Review\Response;

    class Review {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId');
            }

            $reviewListQuery = new Query\Product\Review\GetListByProductId(
                $productId,
                (int)$request->query['page'],
                (int)$request->query['limit'] ?: $config->productReview->itemsInCard
            );
            $curl->prepare($reviewListQuery);
            $curl->execute();

            $response = new Response();
            $response->reviews = (new \EnterRepository\Product\Review())->getObjectListByQuery($reviewListQuery);
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Product\Review {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Review[] */
        public $reviews = [];
    }
}
