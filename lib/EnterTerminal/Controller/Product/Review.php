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
            $reviewRepository = new \EnterRepository\Product\Review();

            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId');
            }

            $response = new Response();

            $reviewListQuery = new Query\Product\Review\GetListByProductId(
                $productId,
                (int)$request->query['page'],
                (int)$request->query['limit'] ?: $config->productReview->itemsInCard
            );
            $curl->prepare($reviewListQuery);

            $curl->execute();

            // отзывы
            foreach ($reviewRepository->getObjectListByQuery($reviewListQuery) as $review) {
                $response->reviews[] = [
                    'score'     => $review->score,
                    'starScore' => $review->starScore,
                    'extract'   => $review->extract,
                    'pros'      => $review->pros,
                    'cons'      => $review->cons,
                    'author'    => $review->author,
                    'source'    => $review->source,
                    'createdAt' => $review->createdAt ? $review->createdAt->getTimestamp() : null,
                ];
            }

            $response->reviewCount = $reviewRepository->countObjectListByQuery($reviewListQuery);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\Product\Review {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Review[] */
        public $reviews = [];
        /** @var int */
        public $reviewCount;
    }
}
