<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\ProductList\Response;

    class ProductList {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\Response
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new \EnterRepository\Product();

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId');
            }

            // список айдишников
            $productIds = [];
            foreach ((array)$request->query['productIds'] as $productId) {
                if (is_scalar($productId)) {
                    $productIds[] = (string)$productId;
                }
            }
            if (!(bool)$productIds) {
                throw new \Exception('Не передан productIds');
            }

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
            if (!$region) {
                throw new \Exception(sprintf('Регион #%s не найден', $regionId));
            }

            // запрос товаров
            $productListQueries = [];
            foreach (array_chunk($productIds, $config->curl->queryChunkSize) as $idsInChunk) {
                $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $region->id);
                $curl->prepare($productListQuery);

                $productListQueries[] = $productListQuery;
            }

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled && (bool)$productIds) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($productIds);
                $curl->prepare($ratingListQuery);
            }

            // запрос списка видео для товаров
            $videoGroupedListQuery = new Query\Product\Media\Video\GetGroupedListByProductIdList($productIds);
            $curl->prepare($videoGroupedListQuery);

            $curl->execute();

            // список товаров
            $productsById = (bool)$productListQueries ? $productRepository->getIndexedObjectListByQueryList($productListQueries) : [];

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // список видео для товаров
            $productRepository->setVideoForObjectListByQuery($productsById, $videoGroupedListQuery);

            // ответ
            $response = new Response();
            $response->productIds = $productIds;
            $response->products = array_values($productsById);
            $response->productCount = count($productsById);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\ProductList {
    use EnterModel as Model;

    class Response {
        /** @var string[] */
        public $productIds = [];
        /** @var Model\Product[] */
        public $products = [];
        /** @var int */
        public $productCount;
    }
}