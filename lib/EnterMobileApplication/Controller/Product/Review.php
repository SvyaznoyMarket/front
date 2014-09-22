<?php

namespace EnterMobileApplication\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller\Product\Review\Response;

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

            $page = (int)$request->query['page'];
            if (!$page) {
                throw new \Exception('Не указан параметр page');
            }

            $limit = (int)$request->query['limit'];
            if (!$limit) {
                throw new \Exception('Не указан параметр limit');
            }

            // ответ
            $response = new Response();

            // подготовка отзывов
            $reviewListQuery = new Query\Product\Review\GetListByProductId(
                $productId,
                $page,
                $limit
            );
            $curl->prepare($reviewListQuery);

            $curl->execute();

            // отзывы
            foreach ((new \EnterRepository\Product\Review())->getObjectListByQuery($reviewListQuery) as $review) {
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

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Product\Review {
    use EnterModel as Model;

    class Response {
        /** @var Model\Product\Review[] */
        public $reviews = [];
    }
}
